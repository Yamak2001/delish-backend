<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workflow;
use App\Models\User;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('role', 'admin')->first();

        $workflows = [
            [
                'workflow_name' => 'Standard Cake Production',
                'description' => 'Standard workflow for all cake orders including mixing, baking, cooling, decorating, and packaging',
                'workflow_type' => 'cakes',
                'workflow_steps' => [
                    [
                        'step_order' => 1,
                        'step_name' => 'Ingredient Preparation',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 30,
                        'required_skills' => ['ingredient_prep'],
                        'instructions' => 'Gather and measure all ingredients according to recipe. Bring dairy products to room temperature.',
                        'quality_checkpoints' => ['Verify ingredient freshness', 'Check measurements accuracy'],
                    ],
                    [
                        'step_order' => 2,
                        'step_name' => 'Mixing and Batter Preparation',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 20,
                        'required_skills' => ['mixing', 'baking_knowledge'],
                        'instructions' => 'Mix ingredients according to recipe method. Avoid overmixing.',
                        'quality_checkpoints' => ['Batter consistency check', 'No lumps verification'],
                    ],
                    [
                        'step_order' => 3,
                        'step_name' => 'Baking',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 40,
                        'required_skills' => ['oven_operation', 'temperature_monitoring'],
                        'instructions' => 'Preheat oven, prepare pans, bake according to recipe timing.',
                        'quality_checkpoints' => ['Toothpick test', 'Visual doneness check', 'Internal temperature'],
                    ],
                    [
                        'step_order' => 4,
                        'step_name' => 'Cooling and Unmolding',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 60,
                        'required_skills' => ['handling'],
                        'instructions' => 'Cool in pan for 10 minutes, then turn out onto cooling racks.',
                        'quality_checkpoints' => ['Structural integrity check', 'Proper cooling verification'],
                    ],
                    [
                        'step_order' => 5,
                        'step_name' => 'Decoration and Assembly',
                        'department' => 'decorating',
                        'estimated_duration_minutes' => 45,
                        'required_skills' => ['decorating', 'frosting_techniques'],
                        'instructions' => 'Apply frosting, decorative elements as specified in order.',
                        'quality_checkpoints' => ['Appearance standards', 'Decoration accuracy', 'Clean presentation'],
                    ],
                    [
                        'step_order' => 6,
                        'step_name' => 'Final Quality Check',
                        'department' => 'quality_control',
                        'estimated_duration_minutes' => 10,
                        'required_skills' => ['quality_assessment'],
                        'instructions' => 'Final inspection for appearance, decoration, and overall quality.',
                        'quality_checkpoints' => ['Overall appearance', 'Order specifications match', 'Ready for packaging'],
                    ],
                    [
                        'step_order' => 7,
                        'step_name' => 'Packaging',
                        'department' => 'packaging',
                        'estimated_duration_minutes' => 15,
                        'required_skills' => ['packaging'],
                        'instructions' => 'Package in appropriate container with labels and care instructions.',
                        'quality_checkpoints' => ['Secure packaging', 'Correct labeling', 'Ready for delivery'],
                    ]
                ],
                'estimated_total_duration_minutes' => 220,
                'created_by_user_id' => $adminUser->id,
                'active_status' => true,
            ],
            [
                'workflow_name' => 'Cupcake Batch Production',
                'description' => 'Optimized workflow for cupcake orders focusing on batch efficiency',
                'workflow_type' => 'cupcakes',
                'workflow_steps' => [
                    [
                        'step_order' => 1,
                        'step_name' => 'Batch Ingredient Prep',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 25,
                        'required_skills' => ['ingredient_prep', 'batch_planning'],
                        'instructions' => 'Prepare ingredients for entire batch. Scale recipe quantities.',
                        'quality_checkpoints' => ['Scaling accuracy', 'Ingredient quality'],
                    ],
                    [
                        'step_order' => 2,
                        'step_name' => 'Batter Mixing',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 15,
                        'required_skills' => ['mixing', 'batch_production'],
                        'instructions' => 'Mix large batch of cupcake batter in commercial mixer.',
                        'quality_checkpoints' => ['Consistent mixing', 'Proper batter consistency'],
                    ],
                    [
                        'step_order' => 3,
                        'step_name' => 'Portioning and Baking',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 35,
                        'required_skills' => ['portioning', 'oven_operation'],
                        'instructions' => 'Portion batter into cupcake liners, bake in batches.',
                        'quality_checkpoints' => ['Consistent portion sizes', 'Even baking', 'Proper rise'],
                    ],
                    [
                        'step_order' => 4,
                        'step_name' => 'Cooling',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 30,
                        'required_skills' => ['handling'],
                        'instructions' => 'Cool cupcakes completely on racks before frosting.',
                        'quality_checkpoints' => ['Complete cooling', 'No damage during handling'],
                    ],
                    [
                        'step_order' => 5,
                        'step_name' => 'Frosting Application',
                        'department' => 'decorating',
                        'estimated_duration_minutes' => 40,
                        'required_skills' => ['piping', 'frosting_techniques'],
                        'instructions' => 'Apply frosting using piping bag or offset spatula.',
                        'quality_checkpoints' => ['Consistent frosting amount', 'Clean application', 'Attractive appearance'],
                    ],
                    [
                        'step_order' => 6,
                        'step_name' => 'Packaging',
                        'department' => 'packaging',
                        'estimated_duration_minutes' => 20,
                        'required_skills' => ['packaging', 'handling'],
                        'instructions' => 'Package in cupcake containers with protective inserts.',
                        'quality_checkpoints' => ['Secure transport packaging', 'Frosting protection', 'Proper labeling'],
                    ]
                ],
                'estimated_total_duration_minutes' => 165,
                'created_by_user_id' => $adminUser->id,
                'active_status' => true,
            ],
            [
                'workflow_name' => 'Cookie Batch Production',
                'description' => 'High-volume cookie production workflow optimized for efficiency',
                'workflow_type' => 'cookies',
                'workflow_steps' => [
                    [
                        'step_order' => 1,
                        'step_name' => 'Large Batch Mixing',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 20,
                        'required_skills' => ['large_batch_mixing', 'recipe_scaling'],
                        'instructions' => 'Mix large batch of cookie dough in commercial mixer.',
                        'quality_checkpoints' => ['Proper dough consistency', 'Even ingredient distribution'],
                    ],
                    [
                        'step_order' => 2,
                        'step_name' => 'Dough Chilling',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 30,
                        'required_skills' => ['dough_handling'],
                        'instructions' => 'Chill dough to proper temperature for easier handling and better texture.',
                        'quality_checkpoints' => ['Proper chilling time', 'Dough firmness'],
                    ],
                    [
                        'step_order' => 3,
                        'step_name' => 'Portioning and Shaping',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 25,
                        'required_skills' => ['portioning', 'cookie_shaping'],
                        'instructions' => 'Portion dough into consistent sizes, shape as required.',
                        'quality_checkpoints' => ['Consistent cookie sizes', 'Proper spacing', 'Uniform shapes'],
                    ],
                    [
                        'step_order' => 4,
                        'step_name' => 'Baking in Batches',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 45,
                        'required_skills' => ['batch_baking', 'timing_management'],
                        'instructions' => 'Bake multiple trays in rotation for consistent results.',
                        'quality_checkpoints' => ['Even browning', 'Proper texture', 'Consistent baking'],
                    ],
                    [
                        'step_order' => 5,
                        'step_name' => 'Cooling and Quality Check',
                        'department' => 'kitchen',
                        'estimated_duration_minutes' => 20,
                        'required_skills' => ['quality_assessment'],
                        'instructions' => 'Cool cookies completely, check for quality standards.',
                        'quality_checkpoints' => ['Complete cooling', 'Texture verification', 'Visual inspection'],
                    ],
                    [
                        'step_order' => 6,
                        'step_name' => 'Packaging',
                        'department' => 'packaging',
                        'estimated_duration_minutes' => 15,
                        'required_skills' => ['bulk_packaging'],
                        'instructions' => 'Package in appropriate containers maintaining freshness.',
                        'quality_checkpoints' => ['Proper packaging', 'Freshness sealing', 'Correct labeling'],
                    ]
                ],
                'estimated_total_duration_minutes' => 155,
                'created_by_user_id' => $adminUser->id,
                'active_status' => true,
            ]
        ];

        foreach ($workflows as $workflowData) {
            Workflow::create($workflowData);
        }

        $this->command->info('Created ' . count($workflows) . ' production workflows');
    }
}