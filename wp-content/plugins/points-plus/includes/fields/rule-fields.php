<?php

/**
 * Registers ACF fields for the Rule custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/fields
 */

namespace PointsPlus\Fields;

if ( function_exists( 'acf_add_local_field_group' ) ) {

    acf_add_local_field_group( array(
        'key' => 'group_rule_details',
        'title' => 'Rule Details',
        'fields' => array(
            array(
                'key' => 'field_rule_name',
                'label' => 'Rule Name',
                'name' => 'rule_name',
                'type' => 'text',
                'instructions' => 'A descriptive name for the rule.',
                'required' => 1,
            ),
            array(
                'key' => 'field_status',
                'label' => 'Status',
                'name' => 'status',
                'type' => 'select',
                'instructions' => 'Whether the rule is active or inactive.',
                'required' => 1,
                'choices' => array(
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ),
                'default_value' => 'inactive',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 0,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_trigger_event',
                'label' => 'Trigger Event',
                'name' => 'trigger_event',
                'type' => 'select',
                'instructions' => 'The event that triggers the rule evaluation.',
                'required' => 1,
                'choices' => array(
                    'user_login' => 'User Logs In',
                    'user_registers' => 'User Registers',
                    'quest_completed' => 'Quest Completed',
                    'post_published' => 'Post Published',
                    'specific_date' => 'Specific Date',
                ),
                'default_value' => false,
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 0,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_conditions',
                'label' => 'Conditions',
                'name' => 'conditions',
                'type' => 'repeater',
                'instructions' => 'Define the conditions that must be met for the rule to apply.',
                'sub_fields' => array(
                    array(
                        'key' => 'field_condition_logic',
                        'label' => 'Condition Logic',
                        'name' => 'condition_logic',
                        'type' => 'select',
                        'instructions' => 'How conditions within this group are combined.',
                        'choices' => array(
                            'AND' => 'AND',
                            'OR' => 'OR',
                        ),
                        'default_value' => 'AND',
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 0,
                        'return_format' => 'value',
                    ),
                    array(
                        'key' => 'field_condition',
                        'label' => 'Condition',
                        'name' => 'condition',
                        'type' => 'repeater',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_field',
                                'label' => 'Field',
                                'name' => 'field',
                                'type' => 'select',
                                'instructions' => 'The data field to check.',
                                'choices' => array(
                                    'user_id' => 'User ID',
                                    'user_login' => 'User Login',
                                    'user_email' => 'User Email',
                                    'user_meta.coin_balance' => 'Coin Balance',
                                    'user_meta.star_balance' => 'Star Balance',
                                    'event_data.quest_key' => 'Quest Key',
                                    'day_of_week' => 'Day of Week',
                                ),
                                'default_value' => false,
                                'allow_null' => 0,
                                'multiple' => 0,
                                'ui' => 0,
                                'return_format' => 'value',
                            ),
                            array(
                                'key' => 'field_operator',
                                'label' => 'Operator',
                                'name' => 'operator',
                                'type' => 'select',
                                'instructions' => 'The operator to use for comparison.',
                                'choices' => array(
                                    '==' => 'Equals',
                                    '!=' => 'Not Equals',
                                    '>' => 'Greater Than',
                                    '<' => 'Less Than',
                                    '>=' => 'Greater Than or Equal To',
                                    '<=' => 'Less Than or Equal To',
                                    'IN' => 'In',
                                    'NOT IN' => 'Not In',
                                    'CONTAINS' => 'Contains',
                                    'NOT CONTAINS' => 'Not Contains',
                                ),
                                'default_value' => '==',
                                'allow_null' => 0,
                                'multiple' => 0,
                                'ui' => 0,
                                'return_format' => 'value',
                            ),
                            array(
                                'key' => 'field_value',
                                'label' => 'Value',
                                'name' => 'value',
                                'type' => 'text',
                                'instructions' => 'The value to compare against.',
                            ),
                        ),
                        'min' => 1,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => 'Add Condition',
                    ),
                ),
                'min' => 1,
                'max' => 0,
                'layout' => 'block',
                'button_label' => 'Add Condition Group',
            ),
            array(
                'key' => 'field_reward_logic',
                'label' => 'Reward Logic',
                'name' => 'reward_logic',
                'type' => 'repeater',
                'instructions' => 'Define the actions to take when the rule is met.',
                'sub_fields' => array(
                    array(
                        'key' => 'field_reward_type',
                        'label' => 'Reward Type',
                        'name' => 'type',
                        'type' => 'select',
                        'instructions' => 'The type of reward to grant.',
                        'choices' => array(
                            'grant_coins' => 'Grant Coins',
                            'grant_stars' => 'Grant Stars',
                            'apply_promotion' => 'Apply Promotion',
                            'custom_function' => 'Custom Function',
                        ),
                        'default_value' => false,
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 0,
                        'return_format' => 'value',
                    ),
                    array(
                        'key' => 'field_reward_parameters',
                        'label' => 'Reward Parameters',
                        'name' => 'parameters',
                        'type' => 'group',
                        'instructions' => 'Parameters for the reward.',
                        'layout' => 'block',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_amount',
                                'label' => 'Amount',
                                'name' => 'amount',
                                'type' => 'number',
                                'instructions' => 'Amount of coins/stars to grant.',
                                'parent_repeater' => 'field_reward_logic',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field' => 'field_reward_type',
                                            'operator' => 'in',
                                            'value' => 'grant_coins,grant_stars',
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'field_reason',
                                'label' => 'Reason',
                                'name' => 'reason',
                                'type' => 'text',
                                'instructions' => 'Reason for granting the reward.',
                                'parent_repeater' => 'field_reward_logic',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field' => 'field_reward_type',
                                            'operator' => 'in',
                                            'value' => 'grant_coins,grant_stars',
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'field_promotion_id',
                                'label' => 'Promotion',
                                'name' => 'promotion_id',
                                'type' => 'post_object',
                                'instructions' => 'The promotion to apply.',
                                'post_type' => array(
                                    0 => 'reward-item',
                                ),
                                'allow_null' => 0,
                                'multiple' => 0,
                                'return_format' => 'id',
                                'ui' => 0,
                                'parent_repeater' => 'field_reward_logic',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field' => 'field_reward_type',
                                            'operator' => '==',
                                            'value' => 'apply_promotion',
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'field_function_name',
                                'label' => 'Function Name',
                                'name' => 'function_name',
                                'type' => 'text',
                                'instructions' => 'The name of the custom function to call.',
                                'parent_repeater' => 'field_reward_logic',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field' => 'field_reward_type',
                                            'operator' => '==',
                                            'value' => 'custom_function',
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'field_function_params',
                                'label' => 'Function Parameters',
                                'name' => 'function_params',
                                'type' => 'textarea',
                                'instructions' => 'Parameters to pass to the custom function (JSON format).',
                                'parent_repeater' => 'field_reward_logic',
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field' => 'field_reward_type',
                                            'operator' => '==',
                                            'value' => 'custom_function',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'min' => 1,
                'max' => 0,
                'layout' => 'block',
                'button_label' => 'Add Reward Action',
            ),
            array(
                'key' => 'field_time_constraints',
                'label' => 'Time Constraints',
                'name' => 'time_constraints',
                'type' => 'group',
                'instructions' => 'Optional: Set time constraints for the rule.',
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_time_enabled',
                        'label' => 'Enable Time Constraint',
                        'name' => 'enabled',
                        'type' => 'true_false',
                        'instructions' => 'Enable time constraints for this rule.',
                        'default_value' => 0,
                        'ui' => 0,
                    ),
                    array(
                        'key' => 'field_valid_from',
                        'label' => 'Valid From',
                        'name' => 'valid_from',
                        'type' => 'date_time_picker',
                        'instructions' => 'The date and time when the rule becomes active.',
                        'return_format' => 'Y-m-d H:i:s',
                        'display_format' => 'Y-m-d H:i:s',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_time_enabled',
                                    'operator' => '==',
                                    'value' => '1',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_valid_until',
                        'label' => 'Valid Until',
                        'name' => 'valid_until',
                        'type' => 'date_time_picker',
                        'instructions' => 'The date and time when the rule expires.',
                        'return_format' => 'Y-m-d H:i:s',
                        'display_format' => 'Y-m-d H:i:s',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_time_enabled',
                                    'operator' => '==',
                                    'value' => '1',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_priority',
                'label' => 'Priority',
                'name' => 'priority',
                'type' => 'number',
                'instructions' => 'The order in which the rule should be evaluated (lower numbers are evaluated first).',
                'default_value' => 0,
            ),
            array(
                'key' => 'field_description',
                'label' => 'Description',
                'name' => 'description',
                'type' => 'textarea',
                'instructions' => 'A description of the rule.',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'rule',
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