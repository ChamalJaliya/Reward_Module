<?php
namespace PointsPlus\Fields;

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_student_redeem_simple',
        'title' => 'Student Redeem Details',
        'fields' => [
            [
                'key' => 'field_student_ref',
                'label' => 'Student',
                'name' => 'student',
                'type' => 'post_object',
                'post_type' => ['student'],
                'return_format' => 'id',
                'ui' => 1,
            ],
            [
                'key' => 'field_reward_item_ref',
                'label' => 'Reward Item',
                'name' => 'reward_item',
                'type' => 'post_object',
                'post_type' => ['reward-item'],
                'return_format' => 'id',
                'ui' => 1,
            ],
            [
                'key' => 'field_claimed_on',
                'label' => 'Claimed On',
                'name' => 'claimed_on',
                'type' => 'date_time_picker',
                'return_format' => 'Y-m-d H:i:s',
            ],
            [
                'key' => 'field_redeem_status',
                'label' => 'Status',
                'name' => 'status',
                'type' => 'select',
                'choices' => [
                    'pending' => 'Pending',
                    'processed' => 'Processed',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ],
                'default_value' => 'pending',
                'ui' => 1,
            ]
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'students_redeems'],
        ]],
    ]);
}
