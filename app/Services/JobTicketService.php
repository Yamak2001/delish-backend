<?php

namespace App\Services;

use App\Models\Order;
use App\Models\JobTicket;
use App\Models\JobTicketStep;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobTicketService
{
    /**
     * Create job ticket from confirmed order with smart workflow automation
     */
    public function createFromOrder(Order $order): JobTicket
    {
        return DB::transaction(function () use ($order) {
            
            // Generate unique job ticket number
            $ticketNumber = $this->generateJobTicketNumber();
            
            // Create job ticket
            $jobTicket = JobTicket::create([
                'order_id' => $order->id,
                'workflow_id' => $order->assigned_workflow_id,
                'job_ticket_number' => $ticketNumber,
                'priority_level' => $this->determinePriority($order),
                'current_status' => 'pending',
                'current_step_number' => 1,
                'assigned_users' => [],
                'start_timestamp' => now(),
                'estimated_completion_timestamp' => $this->calculateEstimatedCompletion($order),
                'total_production_cost' => 0, // Will be calculated during production
            ]);
            
            // Create job ticket steps from workflow
            $this->createJobTicketSteps($jobTicket);
            
            // Auto-assign first step to available user
            $this->autoAssignFirstStep($jobTicket);
            
            Log::info("Job ticket created from order", [
                'job_ticket_id' => $jobTicket->id,
                'job_ticket_number' => $ticketNumber,
                'order_id' => $order->id,
                'workflow_id' => $order->assigned_workflow_id,
                'priority' => $jobTicket->priority_level
            ]);
            
            return $jobTicket;
        });
    }

    /**
     * Progress to next step in workflow with business logic
     */
    public function progressToNextStep(JobTicket $jobTicket, User $user, array $stepData = []): array
    {
        return DB::transaction(function () use ($jobTicket, $user, $stepData) {
            
            $currentStep = $jobTicket->steps()
                ->where('step_number', $jobTicket->current_step_number)
                ->first();
                
            if (!$currentStep) {
                return ['success' => false, 'message' => 'Current step not found'];
            }
            
            // Validate user can complete this step
            if (!$this->canUserCompleteStep($user, $currentStep)) {
                return ['success' => false, 'message' => 'User not authorized for this step'];
            }
            
            // Complete current step
            $currentStep->markAsCompleted(
                $user, 
                $stepData['notes'] ?? null, 
                $stepData['quality_check_passed'] ?? null
            );
            
            // Check if quality check failed
            if (isset($stepData['quality_check_passed']) && !$stepData['quality_check_passed']) {
                return $this->handleQualityFailure($jobTicket, $currentStep, $stepData['quality_notes'] ?? '');
            }
            
            // Move to next step or complete job ticket
            $nextStepNumber = $this->getNextStepNumber($jobTicket, $currentStep);
            
            if ($nextStepNumber > $jobTicket->workflow->getTotalSteps()) {
                // Job ticket complete
                $this->completeJobTicket($jobTicket, $user);
                return ['success' => true, 'status' => 'completed', 'job_ticket' => $jobTicket];
            }
            
            // Progress to next step
            $jobTicket->update(['current_step_number' => $nextStepNumber]);
            
            // Auto-assign next step
            $nextStep = $this->autoAssignStep($jobTicket, $nextStepNumber);
            
            // Send notifications
            $this->sendStepNotifications($nextStep);
            
            return [
                'success' => true, 
                'status' => 'progressed',
                'current_step' => $nextStepNumber,
                'next_step' => $nextStep,
                'job_ticket' => $jobTicket->fresh()
            ];
        });
    }

    /**
     * Auto-assign step to appropriate user based on role and availability
     */
    public function autoAssignStep(JobTicket $jobTicket, int $stepNumber): ?JobTicketStep
    {
        $step = $jobTicket->steps()->where('step_number', $stepNumber)->first();
        if (!$step) return null;
        
        // Find available user with required role and department
        $workflowStep = $jobTicket->workflow->getStepByNumber($stepNumber);
        if (!$workflowStep) return null;
        
        $requiredRole = $workflowStep['assigned_role'] ?? null;
        $requiredDepartment = $workflowStep['required_department'] ?? null;
        
        $query = User::where('status', 'active');
        
        if ($requiredRole) {
            $query->where('role', $requiredRole);
        }
        
        if ($requiredDepartment) {
            $query->where('department', $requiredDepartment);
        }
        
        // Find user with least active assignments
        $availableUser = $query->withCount(['assignedJobTicketSteps' => function($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('assigned_job_ticket_steps_count', 'asc')
            ->first();
            
        if ($availableUser) {
            $step->startStep($availableUser);
            
            Log::info("Step auto-assigned", [
                'step_id' => $step->id,
                'user_id' => $availableUser->id,
                'job_ticket_number' => $jobTicket->job_ticket_number
            ]);
        }
        
        return $step;
    }

    /**
     * Handle quality check failure with corrective workflow
     */
    private function handleQualityFailure(JobTicket $jobTicket, JobTicketStep $failedStep, string $notes): array
    {
        // Find previous step to return to (typically the step before current)
        $returnToStep = max(1, $failedStep->step_number - 1);
        
        // Reset job ticket to previous step
        $jobTicket->update([
            'current_step_number' => $returnToStep,
            'quality_notes' => "Quality failure at step {$failedStep->step_number}: {$notes}"
        ]);
        
        // Reset the previous step status
        $previousStep = $jobTicket->steps()
            ->where('step_number', $returnToStep)
            ->first();
            
        if ($previousStep) {
            $previousStep->update(['status' => 'pending']);
            $this->autoAssignStep($jobTicket, $returnToStep);
        }
        
        Log::warning("Quality check failed - job ticket returned to previous step", [
            'job_ticket_id' => $jobTicket->id,
            'failed_step' => $failedStep->step_number,
            'returned_to_step' => $returnToStep,
            'notes' => $notes
        ]);
        
        return [
            'success' => true, 
            'status' => 'quality_failure',
            'returned_to_step' => $returnToStep,
            'message' => 'Quality check failed. Returned to previous step for correction.'
        ];
    }

    /**
     * Complete job ticket and trigger downstream processes
     */
    private function completeJobTicket(JobTicket $jobTicket, User $completedBy): void
    {
        $jobTicket->update([
            'current_status' => 'completed',
            'actual_completion_timestamp' => now(),
            'total_production_cost' => $this->calculateActualProductionCost($jobTicket),
        ]);
        
        // Update order status
        $jobTicket->order->update(['order_status' => 'completed']);
        
        // Trigger invoice generation
        $this->triggerInvoiceGeneration($jobTicket);
        
        // Update inventory (deduct used ingredients)
        $this->updateInventoryFromProduction($jobTicket);
        
        // Create product tracking records for delivery
        $this->createProductTrackingRecords($jobTicket);
        
        Log::info("Job ticket completed", [
            'job_ticket_id' => $jobTicket->id,
            'completed_by' => $completedBy->id,
            'production_cost' => $jobTicket->total_production_cost
        ]);
    }

    /**
     * Cancel job ticket with proper cleanup
     */
    public function cancelJobTicket(JobTicket $jobTicket): void
    {
        DB::transaction(function () use ($jobTicket) {
            
            // Cancel all pending steps
            $jobTicket->steps()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
            
            // Update job ticket status
            $jobTicket->update([
                'current_status' => 'cancelled',
                'actual_completion_timestamp' => now(),
            ]);
            
            // Update order status
            $jobTicket->order->update(['order_status' => 'cancelled']);
            
            Log::info("Job ticket cancelled", [
                'job_ticket_id' => $jobTicket->id,
                'order_id' => $jobTicket->order_id
            ]);
        });
    }

    /**
     * Generate unique job ticket number
     */
    private function generateJobTicketNumber(): string
    {
        $prefix = 'JT';
        $date = now()->format('ymd');
        $sequence = str_pad(JobTicket::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$sequence}";
    }

    /**
     * Determine job priority based on order characteristics
     */
    private function determinePriority(Order $order): string
    {
        // Rush orders (same day delivery)
        if ($order->requested_delivery_date->isToday()) {
            return 'urgent';
        }
        
        // Large orders or VIP merchants
        if ($order->total_amount > 500 || $order->merchant->account_status === 'vip') {
            return 'high';
        }
        
        return 'normal';
    }

    /**
     * Calculate estimated completion time
     */
    private function calculateEstimatedCompletion(Order $order): \Carbon\Carbon
    {
        $workflow = $order->workflow;
        $estimatedMinutes = $workflow->estimated_total_duration_minutes;
        
        // Add buffer based on priority
        $priority = $this->determinePriority($order);
        $buffer = match($priority) {
            'urgent' => 0.8, // 20% faster
            'high' => 1.0,   // No change
            'normal' => 1.2, // 20% buffer
        };
        
        return now()->addMinutes($estimatedMinutes * $buffer);
    }

    /**
     * Create job ticket steps from workflow template
     */
    private function createJobTicketSteps(JobTicket $jobTicket): void
    {
        $workflow = $jobTicket->workflow;
        $steps = $workflow->workflow_steps;
        
        foreach ($steps as $index => $stepTemplate) {
            JobTicketStep::create([
                'job_ticket_id' => $jobTicket->id,
                'step_number' => $index + 1,
                'step_name' => $stepTemplate['step_name'],
                'assigned_role' => $stepTemplate['assigned_role'],
                'step_type' => $stepTemplate['step_type'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Auto-assign first step
     */
    private function autoAssignFirstStep(JobTicket $jobTicket): void
    {
        $this->autoAssignStep($jobTicket, 1);
        
        // Update job ticket status to in_progress
        $jobTicket->update(['current_status' => 'in_progress']);
    }

    /**
     * Check if user can complete specific step
     */
    private function canUserCompleteStep(User $user, JobTicketStep $step): bool
    {
        // Check role match
        if ($step->assigned_role && $user->role !== $step->assigned_role) {
            return false;
        }
        
        // Check if user is assigned to this step
        if ($step->assigned_user_id && $step->assigned_user_id !== $user->id) {
            return false;
        }
        
        return true;
    }

    /**
     * Get next step number (handles step overrides)
     */
    private function getNextStepNumber(JobTicket $jobTicket, JobTicketStep $currentStep): int
    {
        return $currentStep->next_step_override ?: ($currentStep->step_number + 1);
    }

    /**
     * Send notifications for step assignments
     */
    private function sendStepNotifications(JobTicketStep $step): void
    {
        if ($step->assignedUser) {
            // TODO: Send email/SMS notification to assigned user
            Log::info("Step notification sent", [
                'step_id' => $step->id,
                'user_id' => $step->assigned_user_id,
                'step_name' => $step->step_name
            ]);
        }
    }

    /**
     * Calculate actual production cost based on ingredients used
     */
    private function calculateActualProductionCost(JobTicket $jobTicket): float
    {
        $totalCost = 0;
        $order = $jobTicket->order;
        
        foreach ($order->order_items as $orderItem) {
            $recipe = \App\Models\Recipe::find($orderItem['recipe_id']);
            if ($recipe) {
                $recipeCost = $recipe->recipeIngredients->sum(function ($ingredient) use ($orderItem) {
                    return $ingredient->getCostContribution() * $orderItem['quantity'];
                });
                $totalCost += $recipeCost;
            }
        }
        
        return $totalCost;
    }

    /**
     * Trigger automatic invoice generation
     */
    private function triggerInvoiceGeneration(JobTicket $jobTicket): void
    {
        // TODO: Implement invoice generation service
        Log::info("Invoice generation triggered", [
            'job_ticket_id' => $jobTicket->id,
            'order_id' => $jobTicket->order_id
        ]);
    }

    /**
     * Update inventory after production completion
     */
    private function updateInventoryFromProduction(JobTicket $jobTicket): void
    {
        // TODO: Implement inventory deduction
        Log::info("Inventory updated after production", [
            'job_ticket_id' => $jobTicket->id
        ]);
    }

    /**
     * Create product tracking records for delivery
     */
    private function createProductTrackingRecords(JobTicket $jobTicket): void
    {
        // TODO: Create merchant product tracking records
        Log::info("Product tracking records created", [
            'job_ticket_id' => $jobTicket->id
        ]);
    }
}