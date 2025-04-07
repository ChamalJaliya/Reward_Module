<?php

/**
 * Registers ACF fields for student details.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes/fields
 */

namespace PointsPlus\Fields;

if ( function_exists( 'acf_add_local_field_group' ) ) {

    acf_add_local_field_group( array(
        'key' => 'group_student_details',
        'title' => 'Student Details',
        'fields' => array(
            array(
                'key' => 'field_email',
                'label' => 'Email',
                'name' => 'email',
                'type' => 'email',
            ),
            array(
                'key' => 'field_first_name',
                'label' => 'First Name',
                'name' => 'first_name',
                'type' => 'text',
            ),
            array(
                'key' => 'field_last_name',
                'label' => 'Last Name',
                'name' => 'last_name',
                'type' => 'text',
            ),
            array(
                'key' => 'field_points',
                'label' => 'Points',
                'name' => 'points',
                'type' => 'number',
            ),
            array(
                'key' => 'field_keys',
                'label' => 'Keys',
                'name' => 'keys',
                'type' => 'number',
            ),
            array(
                'key' => 'field_coins',
                'label' => 'Coins',
                'name' => 'coins',
                'type' => 'number',
            ),
            array(
                'key' => 'field_status',
                'label' => 'Status',
                'name' => 'status',
                'type' => 'select',
                'choices' => array( // Add your status choices here!
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'pending' => 'Pending',
                ),
                'default_value' => false,
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 0,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_mobile_number',
                'label' => 'Mobile Number',
                'name' => 'mobile_number',
                'type' => 'text',
            ),
            array(
                'key' => 'field_date',
                'label' => 'Date',
                'name' => 'date',
                'type' => 'date_time_picker',
                'return_format' => 'Y-m-d H:i:s', // Adjust format as needed
                'display_format' => 'F j, Y g:i a', // Adjust display format as needed
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'student',
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