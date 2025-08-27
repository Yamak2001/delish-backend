<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobTicket;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\User;

class JobTicketSeeder extends Seeder
{
    public function run(): void
    {
        $kitchenStaff = User::whereIn('department', ['kitchen', 'production'])->get();
        $managers = User::where('role', 'manager')->get();
        $orders = Order::all();
        $recipes = Recipe::all();

        // Create kitchen staff if none exist
        if ($kitchenStaff->isEmpty()) {
            $kitchenStaffData = [
                ['name' => 'Chef Mohammad', 'email' => 'chef.mohammad@delish.com', 'department' => 'kitchen'],
                ['name' => 'Baker Sara', 'email' => 'baker.sara@delish.com', 'department' => 'kitchen'],
                ['name' => 'Pastry Chef Ahmad', 'email' => 'pastry.ahmad@delish.com', 'department' => 'kitchen'],
                ['name' => 'Production Lead Omar', 'email' => 'production.omar@delish.com', 'department' => 'production']
            ];

            foreach ($kitchenStaffData as $staffData) {
                $staff = User::create([
                    'name' => $staffData['name'],
                    'email' => $staffData['email'],
                    'password' => bcrypt('kitchen123'),
                    'role' => 'staff',
                    'department' => $staffData['department'],
                    'phone_number' => '+962-6-555-' . str_pad(rand(1000, 9999), 4, '0'),
                    'status' => 'active'
                ]);
                $kitchenStaff->push($staff);
            }
        }

        $admin = User::where('role', 'admin')->first();
        $manager = $managers->first() ?? $admin;

        if (!$admin || $recipes->isEmpty()) {
            $this->command->warn('Skipping JobTicketSeeder - missing dependencies');
            return;
        }

        $jobTicketsData = [
            [
                'order' => $orders->first(),
                'recipe_name' => 'Chocolate Cake',
                'quantity' => 3,
                'priority' => 'high',
                'status' => 'completed',
                'scheduled_start' => '2025-08-26T06:00:00Z',
                'actual_start' => '2025-08-26T06:15:00Z',
                'estimated_duration' => 180, // 3 hours
                'actual_duration' => 195,
                'special_instructions' => 'Use premium chocolate - customer is regular VIP'
            ],
            [
                'order' => $orders->first(),
                'recipe_name' => 'Vanilla Cupcakes',
                'quantity' => 24,
                'priority' => 'medium',
                'status' => 'completed',
                'scheduled_start' => '2025-08-26T09:30:00Z',
                'actual_start' => '2025-08-26T09:45:00Z',
                'estimated_duration' => 120, // 2 hours
                'actual_duration' => 110,
                'special_instructions' => 'Standard vanilla buttercream frosting'
            ],
            [
                'order' => $orders->skip(1)->first(),
                'recipe_name' => 'Cookies',
                'quantity' => 50,
                'priority' => 'medium',
                'status' => 'quality_check',
                'scheduled_start' => '2025-08-27T08:00:00Z',
                'actual_start' => '2025-08-27T08:10:00Z',
                'estimated_duration' => 90,
                'special_instructions' => 'Mixed variety pack - 25 chocolate chip, 25 oatmeal raisin'
            ],
            [
                'order' => $orders->skip(1)->first(),
                'recipe_name' => 'Muffins',
                'quantity' => 30,
                'priority' => 'medium',
                'status' => 'in_progress',
                'scheduled_start' => '2025-08-27T10:00:00Z',
                'actual_start' => '2025-08-27T10:05:00Z',
                'estimated_duration' => 75,
                'special_instructions' => 'Blueberry muffins - check fruit distribution'
            ],
            [
                'order' => $orders->skip(2)->first(),
                'recipe_name' => 'Wedding Cake',
                'quantity' => 1,
                'priority' => 'urgent',
                'status' => 'pending',
                'scheduled_start' => '2025-08-29T05:00:00Z',
                'estimated_duration' => 480, // 8 hours
                'special_instructions' => 'VIP wedding - 3-tier cake with custom decorations. Customer will provide decoration specifications.'
            ],
            [
                'order' => $orders->skip(2)->first(),
                'recipe_name' => 'Mini Cheesecakes',
                'quantity' => 100,
                'priority' => 'high',
                'status' => 'pending',
                'scheduled_start' => '2025-08-29T08:00:00Z',
                'estimated_duration' => 240, // 4 hours
                'special_instructions' => 'Assorted flavors: 40 classic, 30 strawberry, 30 chocolate'
            ],
            [
                'order' => $orders->skip(3)->first(),
                'recipe_name' => 'Red Velvet Cupcakes',
                'quantity' => 36,
                'priority' => 'medium',
                'status' => 'pending',
                'scheduled_start' => '2025-08-30T09:00:00Z',
                'estimated_duration' => 150,
                'special_instructions' => 'Cream cheese frosting - extra smooth finish required'
            ],
            [
                'order' => $orders->skip(4)->first(),
                'recipe_name' => 'Tiramisu',
                'quantity' => 12,
                'priority' => 'low',
                'status' => 'pending',
                'scheduled_start' => '2025-09-01T12:00:00Z',
                'estimated_duration' => 180,
                'special_instructions' => 'Hotel contract - individual portions, elegant presentation'
            ],
            [
                'order' => null, // Production order
                'recipe_name' => 'Daily Bread',
                'quantity' => 100,
                'priority' => 'medium',
                'status' => 'completed',
                'scheduled_start' => '2025-08-26T04:00:00Z',
                'actual_start' => '2025-08-26T04:00:00Z',
                'estimated_duration' => 300, // 5 hours
                'actual_duration' => 285,
                'special_instructions' => 'Daily production - fresh bread for retail'
            ],
            [
                'order' => null, // Stock production
                'recipe_name' => 'Croissants',
                'quantity' => 60,
                'priority' => 'low',
                'status' => 'in_progress',
                'scheduled_start' => '2025-08-27T05:00:00Z',
                'actual_start' => '2025-08-27T05:15:00Z',
                'estimated_duration' => 240,
                'special_instructions' => 'Fresh croissants for morning stock - butter must be cold'
            ]
        ];

        foreach ($jobTicketsData as $index => $ticketData) {
            $recipe = $recipes->where('name', 'LIKE', '%' . explode(' ', $ticketData['recipe_name'])[0] . '%')->first();
            $assignedTo = $kitchenStaff->random();
            $qualityChecker = $kitchenStaff->where('name', 'LIKE', '%Chef%')->first() ?? $kitchenStaff->first();

            $workflows = \App\Models\Workflow::all();
            $defaultWorkflow = $workflows->first();
            
            $jobTicket = JobTicket::create([
                'order_id' => $ticketData['order']->id ?? $orders->first()->id, // Use first order as default
                'workflow_id' => $defaultWorkflow->id ?? 1,
                'job_ticket_number' => 'JT-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'priority_level' => match($ticketData['priority']) {
                    'urgent' => 'urgent',
                    'high' => 'high', 
                    default => 'normal'
                },
                'current_status' => match($ticketData['status']) {
                    'completed' => 'completed',
                    'in_progress', 'quality_check' => 'in_progress',
                    default => 'pending'
                },
                'current_step_number' => match($ticketData['status']) {
                    'completed' => 3,
                    'in_progress', 'quality_check' => 2,
                    default => 1
                },
                'assigned_users' => [$assignedTo->id],
                'start_timestamp' => $ticketData['actual_start'] ?? null,
                'estimated_completion_timestamp' => isset($ticketData['scheduled_start']) 
                    ? date('Y-m-d H:i:s', strtotime($ticketData['scheduled_start']) + ($ticketData['estimated_duration'] ?? 120) * 60)
                    : null,
                'actual_completion_timestamp' => $ticketData['status'] === 'completed' && isset($ticketData['actual_start'])
                    ? date('Y-m-d H:i:s', strtotime($ticketData['actual_start']) + ($ticketData['actual_duration'] ?? 120) * 60)
                    : null,
                'total_production_cost' => $ticketData['quantity'] * 15.00, // Estimated production cost per item
                'quality_notes' => match($ticketData['status']) {
                    'completed' => 'Quality approved - meets all standards',
                    'quality_check' => 'Under quality review - texture and appearance check',
                    default => $ticketData['special_instructions']
                }
            ]);
        }

        $this->command->info('✅ Created ' . count($jobTicketsData) . ' production job tickets');
        $this->command->info('   • Status: Completed(3), In Progress(2), Quality Check(1), Pending(4)');
        $this->command->info('   • Priority: Urgent(1), High(2), Medium(5), Low(2)');
        $this->command->info('   • Created ' . $kitchenStaff->count() . ' kitchen staff members');
    }
}