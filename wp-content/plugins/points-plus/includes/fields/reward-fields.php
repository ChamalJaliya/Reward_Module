<?php

/**
 * Registers ACF fields for the Reward Item custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/fields
 */

namespace PointsPlus\Fields;

if ( function_exists( 'acf_add_local_field_group' ) ) {

    acf_add_local_field_group( array(
        'key' => 'group_promotion_details',
        'title' => 'Promotion Details',
        'fields' => array(
            array(
                'key' => 'field_promotion_name',
                'label' => 'Promotion Name',
                'name' => 'promotion_name',
                'type' => 'text',
                'instructions' => 'The name of the promotion.',
                'required' => 1,
            ),
            array(
                'key' => 'field_promotion_type',
                'label' => 'Promotion Type',
                'name' => 'promotion_type',
                'type' => 'select',
                'instructions' => 'The type of promotion.',
                'required' => 1,
                'choices' => array(
                    'reload' => 'Reload-Based',
                    'multiplication' => 'Multiplication-Based',
                ),
                'default_value' => false,
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 0,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_description',
                'label' => 'Description',
                'name' => 'description',
                'type' => 'textarea',
                'instructions' => 'A brief description of the promotion.',
            ),
            array(
                'key' => 'field_valid_from',
                'label' => 'Valid From',
                'name' => 'valid_from',
                'type' => 'date_time_picker',
                'instructions' => 'The date and time when the promotion becomes active.',
                'return_format' => 'Y-m-d H:i:s',
            ),
            array(
                'key' => 'field_valid_until',
                'label' => 'Valid Until',
                'name' => 'valid_until',
                'type' => 'date_time_picker',
                'instructions' => 'The date and time when the promotion expires.',
                'return_format' => 'Y-m-d H:i:s',
            ),
            array(
                'key' => 'field_required_coins',
                'label' => 'Required Coins',
                'name' => 'required_coins',
                'type' => 'number',
                'instructions' => 'The number of coins required to redeem this promotion.',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_promotion_type',
                            'operator' => '==',
                            'value' => 'reload',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_reload_value',
                'label' => 'Reload Value',
                'name' => 'reload_value',
                'type' => 'number',
                'instructions' => 'The value of the mobile reload offered by this promotion.',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_promotion_type',
                            'operator' => '==',
                            'value' => 'reload',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_multiplication_type',
                'label' => 'Multiplication Type',
                'name' => 'multiplication_type',
                'type' => 'select',
                'instructions' => 'What reward should be multiplied.',
                'choices' => array(
                    'coins' => 'Coins',
                    'stars' => 'Stars',
                    'both' => 'Both',
                ),
                'default_value' => false,
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 0,
                'return_format' => 'value',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_promotion_type',
                            'operator' => '==',
                            'value' => 'multiplication',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_multiplication_factor',
                'label' => 'Multiplication Factor',
                'name' => 'multiplication_factor',
                'type' => 'number',
                'instructions' => 'The factor by which the reward should be multiplied (e.g., 2 for double).',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_promotion_type',
                            'operator' => '==',
                            'value' => 'multiplication',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_cooldown_period',
                'label' => 'Cooldown Period (in seconds)',
                'name' => 'cooldown_period',
                'type' => 'number',
                'instructions' => 'The time (in seconds) a user must wait after redeeming this promotion before they can redeem it again.',
                'default_value' => 0,
            ),
            array(
                'key' => 'field_redemption_limit',
                'label' => 'Redemption Limit',
                'name' => 'redemption_limit',
                'type' => 'number',
                'instructions' => 'The maximum number of times this promotion can be redeemed (leave blank for unlimited).',
            ),
            array(
                'key' => 'field_required_quests',
                'label' => 'Required Quests',
                'name' => 'required_quests',
                'type' => 'relationship',
                'instructions' => 'Select the quest(s) that need to be completed to trigger this multiplication promotion.',
                'post_type' => array(
                    0 => 'quest',
                ),
                'filters' => array(
                    0 => 'search',
                ),
                'return_format' => 'post_object',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_promotion_type',
                            'operator' => '==',
                            'value' => 'multiplication',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_quest_completion_count',
                'label' => 'Number of Quests to Complete',
                'name' => 'quest_completion_count',
                'type' => 'number',
                'instructions' => 'The number of selected quests the user needs to complete (e.g., complete ANY 2). Leave blank or 0 if all selected quests must be completed.',
                'default_value' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_promotion_type',
                            'operator' => '==',
                            'value' => 'multiplication',
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
                    'value' => 'reward-item',
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