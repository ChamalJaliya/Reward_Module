<?php

/**
 * Registers ACF fields for the Quest custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/fields
 */

namespace PointsPlus\Fields;

if ( function_exists( 'acf_add_local_field_group' ) ) {

    acf_add_local_field_group( array(
        'key' => 'group_quest_details',
        'title' => 'Quest Details',
        'fields' => array(
            array(
                'key' => 'field_quest_key',
                'label' => 'Quest Key',
                'name' => 'quest_key',
                'type' => 'text',
                'instructions' => 'A unique identifier for this quest (used in code).',
                'required' => 1,
            ),
            array(
                'key' => 'field_quest_name',
                'label' => 'Quest Name',
                'name' => 'quest_name',
                'type' => 'text',
                'instructions' => 'The user-friendly name of the quest.',
                'required' => 1,
            ),
            array(
                'key' => 'field_description',
                'label' => 'Description',
                'name' => 'description',
                'type' => 'textarea',
                'instructions' => 'A detailed description of the quest.',
            ),
            array(
                'key' => 'field_points_reward',
                'label' => 'Points Reward',
                'name' => 'points_reward',
                'type' => 'number',
                'instructions' => 'The number of points awarded upon completion.',
            ),
            // Add other quest-specific fields here as needed
            array(
                'key' => 'field_completion_criteria',
                'label' => 'Completion Criteria',
                'name' => 'completion_criteria',
                'type' => 'select',
                'instructions' => 'How the quest is completed.',
                'choices' => array(
                    'action' => 'Specific Action',
                    'time' => 'Time Limit',
                    'event' => 'Specific Event',
                ),
            ),
            array(
                'key' => 'field_target_value',
                'label' => 'Target Value',
                'name' => 'target_value',
                'type' => 'number',
                'instructions' => 'The target value for completion (e.g., 5 for "complete 5 tasks").',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_completion_criteria',
                            'operator' => '!=',
                            'value' => 'event',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_time_limit',
                'label' => 'Time Limit (seconds)',
                'name' => 'time_limit',
                'type' => 'number',
                'instructions' => 'The time limit for the quest in seconds (optional).',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_completion_criteria',
                            'operator' => '==',
                            'value' => 'time',
                        ),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'quest',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
    ) );

}