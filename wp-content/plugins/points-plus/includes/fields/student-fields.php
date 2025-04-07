<?php
/**
 * Registers ACF fields for the Student custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/fields
 */

namespace PointsPlus\Fields;

if ( function_exists( 'acf_add_local_field_group' ) ) {

    acf_add_local_field_group( array(
        'key' => 'group_student_details',
        'title' => 'Student Details',
        'fields' => array(
            array(
                'key' => 'field_student_email',
                'label' => 'Email',
                'name' => 'email',
                'type' => 'email',
                'instructions' => 'Enter the student\'s email address.',
                'required' => 1,
            ),
            array(
                'key' => 'field_student_first_name',
                'label' => 'First Name',
                'name' => 'first_name',
                'type' => 'text',
                'instructions' => 'Enter the student\'s first name.',
                'required' => 1,
            ),
            array(
                'key' => 'field_student_last_name',
                'label' => 'Last Name',
                'name' => 'last_name',
                'type' => 'text',
                'instructions' => 'Enter the student\'s last name.',
                'required' => 1,
            ),
            array(
                'key' => 'field_student_courses',
                'label' => 'Courses',
                'name' => 'courses',
                'type' => 'textarea',
                'instructions' => 'List courses the student is enrolled in.',
            ),
            array(
                'key' => 'field_student_points',
                'label' => 'Points',
                'name' => 'points',
                'type' => 'number',
                'instructions' => 'Total points earned by the student.',
            ),
            array(
                'key' => 'field_student_keys',
                'label' => 'Keys',
                'name' => 'keys',
                'type' => 'number',
                'instructions' => 'Number of keys awarded to the student.',
            ),
            array(
                'key' => 'field_student_coins',
                'label' => 'Coins',
                'name' => 'coins',
                'type' => 'number',
                'instructions' => 'Total coins the student has.',
            ),
            array(
                'key' => 'field_student_status',
                'label' => 'Status',
                'name' => 'status',
                'type' => 'select',
                'instructions' => 'Select the student\'s status.',
                'choices' => array(
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ),
                'required' => 1,
            ),
            array(
                'key' => 'field_student_mobile',
                'label' => 'Mobile Number',
                'name' => 'mobile_number',
                'type' => 'text',
                'instructions' => 'Enter the mobile number of the student.',
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
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => '',
        'active'                => true,
        'description'           => '',
    ) );
}
