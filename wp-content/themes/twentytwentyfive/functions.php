<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( get_parent_theme_file_uri( 'assets/css/editor-style.css' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

// Enqueues style.css on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues style.css on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;

// --- Start of Quest Playing Code --- //

add_action('wp_ajax_play_quest', 'handle_play_quest_ajax');
add_action('wp_ajax_nopriv_play_quest', 'handle_play_quest_ajax'); // Allows non-logged-in users to trigger this

function handle_play_quest_ajax()
{
    // 1. Security Check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'play_quest_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed!']);
        return;
    }
    // 2. Get Quest ID
    if (!isset($_POST['quest_id']) || !is_numeric($_POST['quest_id'])) {
        wp_send_json_error(['message' => 'Invalid Quest ID.']);
        return;
    }
    $quest_id = intval($_POST['quest_id']);
    // 3. Find the Hardcoded Student Post ID by Email
    $target_email = 'cjaliya.sln2@gmail.com'; // The student who gets the points
    $student_post_id = get_student_post_id_by_email($target_email);
    if (!$student_post_id) {
        wp_send_json_error(['message' => 'Could not find student profile for ' . $target_email]);
        return;
    }
    // 4. Get Quest Rewards (Check ACF Field Names are correct!)
    $points_reward = get_field('points_reward', $quest_id);
    $coins_reward = get_field('coins_reward', $quest_id);
    $points_reward = is_numeric($points_reward) ? intval($points_reward) : 0;
    $coins_reward = is_numeric($coins_reward) ? intval($coins_reward) : 0;
    // 5. Get Target Student's Current Points/Coins (Check ACF Field Names are correct!)
    $current_points = get_field('points', $student_post_id);
    $current_coins = get_field('coins', $student_post_id);
    $current_points = is_numeric($current_points) ? intval($current_points) : 0;
    $current_coins = is_numeric($current_coins) ? intval($current_coins) : 0;
    // 6. Calculate New Totals
    $new_points = $current_points + $points_reward;
    $new_coins = $current_coins + $coins_reward;
    // 7. Update Target Student's Points/Coins (Check ACF Field Names are correct!)
    $points_updated = update_field('points', $new_points, $student_post_id);
    $coins_updated = update_field('coins', $new_coins, $student_post_id);
    // 8. Send Response
//    if ($points_updated && $coins_updated) {
//        wp_send_json_success([
//            'message' => "Rewards added to {$target_email}: +{$points_reward} Points, +{$coins_reward} Coins.",
//            'new_points' => $new_points,
//            'new_coins' => $new_coins
//        ]);
//    } else {
//        wp_send_json_error(['message' => 'Failed to update student records for ' . $target_email]);
//    }
    // 8. Process Notifications and Send Response
    // ***** CHECK IF UPDATE WAS SUCCESSFUL *****
    if ($points_updated && $coins_updated) {

        // Email content setup
        $quest_post = get_post($quest_id);
        $quest_title = $quest_post ? $quest_post->post_title : 'Unknown Quest';
        $subject = "Quest Completed: " . esc_html($quest_title);

        $message = "Hello,\n\n";
        $message .= "You have successfully completed the quest: " . esc_html($quest_title) . "\n\n";
        $message .= "You've earned:\n";
        $message .= "- {$points_reward} Points\n";
        $message .= "- {$coins_reward} Coins\n\n";
        $message .= "Your new totals are:\n";
        $message .= "- {$new_points} Points\n";
        $message .= "- {$new_coins} Coins\n\n";
        $message .= "Keep up the good work!\n\n";
        $message .= "— The Quest Team";

        // Email headers
        $headers = array('Content-Type: text/plain; charset=UTF-8');

        // Send the email
        $sent = wp_mail($target_email, $subject, $message, $headers);

        // Optional: Log mail status
        if ($sent) {
            error_log("✅ Mail sent to {$target_email}");
        } else {
            error_log("❌ Mail failed to send to {$target_email}");
        }
        // --- 2. Create In-Site Notification ---
        // Prepare message for the notification repeater
        $notification_message = "Completed '" . esc_html($quest_title) . "': +" . esc_html($points_reward) . " Points, +" . esc_html($coins_reward) . " Coins.";
        // Call the function to add the notification to the student's ACF repeater
        // Assumes $student_post_id was successfully retrieved earlier in the main function
        add_notification_to_student_cpt($student_post_id, $notification_message);
        // --- End In-Site Notification ---
        // Final AJAX success response
        wp_send_json_success([
            'message' => "Rewards added! +{$points_reward} Points, +{$coins_reward} Coins.",
            'new_points' => $new_points,
            'new_coins' => $new_coins
        ]);

    } else {
        // Send error response if update failed
        wp_send_json_error(['message' => 'Failed to update student records for ' . esc_html($target_email)]);
    }
}

/** Helper function to find a student CPT post ID by their email ACF field. */
function get_student_post_id_by_email($email)
{
    if (empty($email)) {
        return false;
    }
    $args = array(
        'post_type' => 'student', 'posts_per_page' => 1,
        'meta_query' => array(array('key' => 'email', 'value' => $email, 'compare' => '=',)),
        'fields' => 'ids', 'post_status' => 'publish',
        'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false,
    );
    $student_query = new WP_Query($args);
    if ($student_query->have_posts()) {
        return $student_query->posts[0];
    }
    return false;
}

// --- Start of Display Quests Shortcode --- //
function display_quests_shortcode_function()
{
    $output = '';
    $args = array('post_type' => 'quest', 'posts_per_page' => -1, 'post_status' => 'publish');
    $quests_query = new WP_Query($args);
    if ($quests_query->have_posts()) {
        $output .= '<div class="quests-list">';
        while ($quests_query->have_posts()) {
            $quests_query->the_post();
            $quest_id = get_the_ID();
            $points_reward = get_field('points_reward', $quest_id) ?: 0;
            $coins_reward = get_field('coins_reward', $quest_id) ?: 0;
            $output .= '<div class="quest-item" >';
            $output .= '<h3>' . get_the_title() . '</h3>';
            $output .= '<p>Rewards: ' . esc_html($points_reward) . ' Points, ' . esc_html($coins_reward) . ' Coins</p>';
            $output .= '<button class="play-quest-button" data-quest-id="' . esc_attr($quest_id) . '">Play Quest</button>';
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
    } else {
        $output .= '<p>No quests available at the moment.</p>';
    }
    return $output;
}

add_shortcode('display_quests', 'display_quests_shortcode_function');

// --- Start of Enqueue Script --- //
function my_enqueue_quest_scripts()
{
    wp_enqueue_script( 'quest-handler', // Handle name can be more generic now
        get_template_directory_uri() . '/js/quest-handler.js',
        array('jquery'), '1.1', // Increment version
        true );

    // Pass data from PHP to JavaScript
    wp_localize_script('quest-handler', 'quest_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'play_nonce'    => wp_create_nonce('play_quest_nonce'), // Nonce for playing
        'fetch_nonce' => wp_create_nonce('notification_nonce'), // Nonce for fetching (optional but recommended)
        'mark_read_nonce' => wp_create_nonce('notification_nonce'), // Nonce for marking read (optional but recommended)
        // Pass the student identifier (still hardcoded for now)
        'student_identifier' => 'cjaliya.sln2@gmail.com'
    ));
    // } // End if is_page() - remove if global

    // Ensure Dashicons are loaded
    wp_enqueue_style( 'dashicons' );
}

add_action('wp_enqueue_scripts', 'my_enqueue_quest_scripts');

function my_enqueue_reward_scripts() {
    error_log('my_enqueue_reward_scripts() is running!');
    wp_enqueue_script(
        'reward-handler',
        get_template_directory_uri() . '/js/reward-handler.js',
        array('jquery'),
        '1.0', // Start with a new version number
        true
    );

    wp_localize_script(
        'reward-handler',
        'reward_ajax_object', // Use a different object name
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'redeem_reward_nonce' => wp_create_nonce('redeem_reward_nonce'),
            'student_identifier' => 'cjaliya.sln2@gmail.com', // Remember to replace with dynamic value!
            'ajax_error_message' => __('An error occurred. Please try again.', 'your-theme-text-domain')
        )
    );

    wp_enqueue_style('dashicons'); // If reward scripts also use Dashicons
}

add_action('wp_enqueue_scripts', 'my_enqueue_reward_scripts');

// --- End of ALL Custom Code --- //


// --- Start of Header Info Shortcode --- //

/**
 * Shortcode to display student points, coins, notification bell, and rewards dropdown.
 * Usage: [student_header_info]
 */
function student_header_info_shortcode_function() {
    // Hardcode the email of the student
    $target_email   = 'cjaliya.sln2@gmail.com'; // <<< MAKE SURE THIS IS CORRECT
    $student_post_id = get_student_post_id_by_email($target_email);

    $points        = 0;
    $coins         = 0;
    $unread_count  = 0;
    $rewards_output = '';

    if ($student_post_id && function_exists('get_field')) {
        // Get Points & Coins
        $points = get_field('points', $student_post_id) ?: 0;
        $coins  = get_field('coins', $student_post_id) ?: 0;
        $points = is_numeric($points) ? intval($points) : 0;
        $coins  = is_numeric($coins) ? intval($coins) : 0;

        // Get Notifications and count unread
        $repeater_field_key = 'student_notifications';
        $notifications     = get_field($repeater_field_key, $student_post_id);
        if (is_array($notifications)) {
            foreach ($notifications as $note) {
                if (isset($note['is_read']) && !$note['is_read']) {
                    $unread_count++;
                }
            }
        }

        // --- Fetch Reward Items ---
        $reward_items = get_posts(array(
            'post_type'      => 'reward-item',
            'posts_per_page' => -1,
        ));

        if ($reward_items) {
            $rewards_output .= '<div class="rewards-dropdown" style="display: none;">'; // Initially hidden
            $rewards_output .= '<ul>';
            foreach ($reward_items as $post) {
                $reward_name        = get_the_title($post->ID);
                $reward_description = get_the_content(null, false, $post->ID);
                $required_coins     = get_field('required_coins', $post->ID) ?: 0;
                $promotion_type     = get_field('promotion_type', $post->ID) ?: '';
                $reload_value       = get_field('reload_value', $post->ID) ?: '';
                $multiplication_type    = get_field('multiplication_type', $post->ID) ?: '';
                $multiplication_factor  = get_field('multiplication_factor', $post->ID) ?: '';
                $additional_type    = get_field('additional_type', $post->ID) ?: '';
                $additional_reward  = get_field('additional_reward', $post->ID) ?: '';
                $client_description = get_field('client_description', $post->ID) ?: '';
                $reward_image       = get_the_post_thumbnail($post->ID, 'thumbnail');

                if ($coins >= $required_coins) {
                    $rewards_output .= '<li class="reward-item">';
                    if ($reward_image) {
                        $rewards_output .= '<div class="reward-image">' . $reward_image . '</div>';
                    }
                    $rewards_output .= '<span class="reward-name">' . esc_html($reward_name) . '</span><br>';
                    if ($client_description) {
                        $x_value = 0; // Default values for Points
                        $y_value = 0; // Default values for Coins

                        if ($promotion_type === 'addition') {
                            if ($additional_type === 'Points' || $additional_type === 'Both') {
                                $x_value = $additional_reward;
                            }
                            if ($additional_type === 'Coins' || $additional_type === 'Both') {
                                $y_value = $additional_reward;
                            }
                        }

                        $replaced_description = str_replace(
                            array('[X]', '[Y]'),
                            array(esc_html($x_value), esc_html($y_value)),
                            $client_description
                        );

                        $rewards_output .= '<span class="reward-client-description">' . wp_kses_post($replaced_description) . '</span><br>';
                    }
                    if ($reward_description) {
                        $rewards_output .= '<span class="reward-description">' . wp_kses_post($reward_description) . '</span><br>';
                    }
                    if ($required_coins > 0) {
                        $rewards_output .= '<span class="reward-cost">Requires: ' . esc_html($required_coins) . ' Coins</span><br>';
                    }

                    // Conditional rendering based on promotion_type
                    if ($promotion_type === 'reload' && $reload_value) {
                        $rewards_output .= '<span class="reward-detail">Reload-Based: ' . esc_html($reload_value) . '</span><br>';
                    } elseif ($promotion_type === 'multiplication' && $multiplication_type && $multiplication_factor) {
                        $rewards_output .= '<span class="reward-detail">Multiplication-Based (' . esc_html($multiplication_type) . '): ×' . esc_html($multiplication_factor) . '</span><br>';
                    } elseif ($promotion_type === 'addition' && $additional_type && $additional_reward) {
                        $rewards_output .= '<span class="reward-detail">Addition-Based (' . esc_html($additional_type) . '): +' . esc_html($additional_reward) . '</span><br>';
                    }

                    $rewards_output .= '<button class="redeem-button" data-reward-id="' . esc_attr($post->ID) . '">Redeem</button>';
                    $rewards_output .= '</li>';
                }
            }
            $rewards_output .= '</ul>';
            $rewards_output .= '</div>';
        }
        // --- End Fetch Reward Items ---
    }

    // Prepare HTML output
    $output = '<div class="student-header-info">';
    $output .= '<span class="student-points">Points: ' . esc_html($points) . '</span>';
    $output .= '<span class="student-coins">Coins: ' . esc_html($coins) . '</span>';

    // Rewards Icon Area (Wrapper)
    $output .= '<div class="rewards-icon-area" style="position: relative; cursor: pointer;">';
    $output .= '<span class="student-rewards-icon dashicons dashicons-tickets"></span>';
    $output .= $rewards_output; // Add the hidden rewards content
    $output .= '</div>'; // End wrapper

    // Notification Bell Area
    $output .= '<div class="notification-bell-area" data-student-identifier="' . esc_attr($target_email) . '" style="position: relative; cursor: pointer;">';
    $output .= '<span class="student-notification-icon dashicons dashicons-bell"></span>';
    if ($unread_count > 0) {
        $output .= '<span class="notification-count-badge">' . esc_html($unread_count) . '</span>';
    } else {
        $output .= '<span class="notification-count-badge" style="display: none;">0</span>';
    }
    // Hidden notifications dropdown (will be populated by JS if you implement it)
    $output .= '<div class="notifications-dropdown" style="display: none;"></div>';
    $output .= '</div>'; // end .notification-bell-area

    $output .= '</div>'; // end .student-header-info
    return $output;
}

add_shortcode('student_header_info', 'student_header_info_shortcode_function');

/**
 * Ensure Dashicons are loaded on the frontend if needed.
 * Twenty Twenty-Five might load them already, but this ensures it.
 */
add_action('wp_enqueue_scripts', 'enqueue_dashicons_front_end');
function enqueue_dashicons_front_end()
{
    wp_enqueue_style('dashicons');
}

// --- End of Header Info Shortcode --- //

/**
 * Adds a notification entry to a student CPT's ACF repeater field.
 *
 * @param int $student_post_id The Post ID of the student CPT.
 * @param string $message The notification message content.
 */
function add_notification_to_student_cpt($student_post_id, $message)
{
    // Ensure ACF functions exist to prevent errors if ACF is inactive
    if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
        return false;
    }

    // Field key for the repeater (must match your ACF setup)
    $repeater_field_key = 'student_notifications';

    // Get existing notifications or initialize an empty array
    $notifications = get_field($repeater_field_key, $student_post_id) ?: [];
    if (!is_array($notifications)) {
        $notifications = [];
    }

    // Add the new notification as an array matching sub-field keys
    $notifications[] = [
        'message' => $message,
        'is_read' => false, // Or 0, depending on ACF True/False return format
        'timestamp' => current_time('mysql'),
        // 'link' => '', // Optional: Add a link if needed
    ];

    // Update the repeater field for the specific student post
    $success = update_field($repeater_field_key, $notifications, $student_post_id);

    return $success;
}

// --- AJAX Handler to Fetch Notifications --- //
add_action('wp_ajax_fetch_student_notifications', 'fetch_student_notifications_ajax');
add_action('wp_ajax_nopriv_fetch_student_notifications', 'fetch_student_notifications_ajax');
function fetch_student_notifications_ajax() {
    // Basic security check
    // check_ajax_referer('notification_nonce', 'nonce'); // Consider adding nonce check

    // Use the hardcoded email passed from JS (or find student ID)
    $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'cjaliya.sln2@gmail.com';
    $student_post_id = get_student_post_id_by_email($student_identifier); // Assumes this function exists

    if (!$student_post_id || !function_exists('get_field')) {
        wp_send_json_error(['message' => 'Could not find student or ACF.']);
        return;
    }

    $repeater_field_key = 'student_notifications'; // Must match ACF setup
    $notifications_data = get_field($repeater_field_key, $student_post_id);
    $notifications_to_send = [];

    if (is_array($notifications_data)) {
        // Reverse array to show newest first, and add row index
        $notifications_data = array_reverse($notifications_data, true); // Keep original keys (indices)

        foreach ($notifications_data as $index => $note) {
            // Ensure required fields exist before sending
            if (isset($note['message']) && isset($note['timestamp']) && isset($note['is_read'])) {
                $notifications_to_send[] = [
                    'message'   => wp_kses_post($note['message']), // Sanitize message content
                    'timestamp' => esc_html($note['timestamp']),
                    'is_read'   => (bool)$note['is_read'],
                    'index'     => $index // Send the original row index
                ];
            }
        }
    }

    wp_send_json_success(['notifications' => $notifications_to_send]);
}


// --- AJAX Handler to Mark Notification as Read --- //
add_action('wp_ajax_mark_notification_read', 'mark_notification_read_ajax');
add_action('wp_ajax_nopriv_mark_notification_read', 'mark_notification_read_ajax');
function mark_notification_read_ajax() {
    // Basic security check
    // check_ajax_referer('notification_nonce', 'nonce'); // Consider adding nonce check


    $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'cjaliya.sln2@gmail.com';
    $notification_index = isset($_POST['notification_index']) ? intval($_POST['notification_index']) : -1;
    // Mark all?
    $mark_all = isset($_POST['mark_all']) && $_POST['mark_all'] === 'true';

    $student_post_id = get_student_post_id_by_email($student_identifier);

    if (!$student_post_id || !function_exists('get_field') || !function_exists('update_field')) {
        wp_send_json_error(['message' => 'Could not find student or ACF.']);
        return;
    }

    $repeater_field_key = 'student_notifications';
    $notifications = get_field($repeater_field_key, $student_post_id);
    $updated = false;
    $unread_count = 0;

    if (is_array($notifications)) {
        if ($mark_all) {
            foreach ($notifications as $index => $note) {
                if (isset($note['is_read']) && !$note['is_read']) {
                    $notifications[$index]['is_read'] = true; // Mark as read
                    $updated = true;
                }
            }
            // After marking all, unread count is 0
            $unread_count = 0;
        } elseif ($notification_index >= 0 && isset($notifications[$notification_index])) {
            // Mark specific notification if it exists and is unread
            if (isset($notifications[$notification_index]['is_read']) && !$notifications[$notification_index]['is_read']) {
                $notifications[$notification_index]['is_read'] = true; // Mark as read
                $updated = true;
            }
            // Recalculate unread count after update
            foreach ($notifications as $note) {
                if (isset($note['is_read']) && !$note['is_read']) {
                    $unread_count++;
                }
            }
        } else {
            // If index invalid, just recalculate count without updating
            foreach ($notifications as $note) {
                if (isset($note['is_read']) && !$note['is_read']) {
                    $unread_count++;
                }
            }
        }


        // If any change was made, update the entire repeater field
        if ($updated) {
            $update_success = update_field($repeater_field_key, $notifications, $student_post_id);
            if(!$update_success) {
                error_log("Failed to update repeater field when marking notification read for student ID: " . $student_post_id);
                // Decide if you want to send error back or just proceed
            }
        }

        wp_send_json_success(['new_unread_count' => $unread_count]);

    } else {
        wp_send_json_error(['message' => 'No notifications found to update.']);
    }
}

add_action('wp_ajax_claim_daily_reward', 'claim_daily_reward_ajax');
add_action('wp_ajax_nopriv_claim_daily_reward', 'claim_daily_reward_ajax');

function claim_daily_reward_ajax() {
    // Security Check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'daily_reward_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed!']);
        return;
    }

    // Get the student identifier (email)
    $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : '';
    if (empty($student_identifier)) {
        wp_send_json_error(['message' => 'Student identifier (email) is missing.']);
        return;
    }

    // Find the student's post ID
    $student_post_id = get_student_post_id_by_email($student_identifier); // Assuming you have this function
    if (!$student_post_id) {
        wp_send_json_error(['message' => 'Could not find student profile.']);
        return;
    }

    // --- DEFINE YOUR REWARD VALUES HERE ---
    $reward_points = 20; // Example: 20 points
    $reward_coins = 10;   // Example: 10 coins
    // --- END REWARD VALUES ---

    // Check eligibility
    if (!is_student_eligible_for_daily_reward($student_post_id)) {
        wp_send_json_error(['message' => 'You are not eligible to claim the daily reward yet.']);
        return;
    }

    // Grant the reward
    $reward_granted = grant_daily_reward($student_post_id, $reward_points, $reward_coins);

    if ($reward_granted) {
        // Get updated points and coins for the response
        $updated_points = get_field('points', $student_post_id) ?: 0;
        $updated_coins = get_field('coins', $student_post_id) ?: 0;

        wp_send_json_success([
            'message' => 'Daily reward claimed successfully!',
            'points' => $updated_points,
            'coins' => $updated_coins
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to grant daily reward.']);
    }
}

// --- Start of Daily Reward Functions --- //

/**
 * Checks if a student is eligible to claim their daily reward.
 *
 * @param int $student_post_id The Post ID of the student CPT.
 * @return bool True if eligible, false otherwise.
 */
function is_student_eligible_for_daily_reward($student_post_id) {
    if (!function_exists('get_field') || !$student_post_id) {
        return false; // ACF not active or invalid student
    }

    $last_claimed = get_field('last_daily_reward_claimed', $student_post_id);

    if (!$last_claimed) {
        return true; // Never claimed before
    }

    $last_claimed_timestamp = strtotime($last_claimed);
    $now = time();
    $time_difference = $now - $last_claimed_timestamp;

    return $time_difference >= 24 * 60 * 60; // 24 hours in seconds
}

/**
 * Grants the daily reward to a student.
 *
 * @param int $student_post_id The Post ID of the student CPT.
 * @param int $reward_points The number of points to award.
 * @param int $reward_coins The number of coins to award.
 * @return bool True on success, false on failure.
 */
function grant_daily_reward($student_post_id, $reward_points, $reward_coins) {
    if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
        return false; // ACF not active or invalid student
    }

    // Get current values (with defaults to avoid errors)
    $current_points = get_field('points', $student_post_id) ?: 0;
    $current_coins = get_field('coins', $student_post_id) ?: 0;

    // Calculate new totals
    $new_points = $current_points + $reward_points;
    $new_coins = $current_coins + $reward_coins;

    // Update fields
    $points_updated = update_field('points', $new_points, $student_post_id);
    $coins_updated = update_field('coins', $new_coins, $student_post_id);
    $last_claimed_updated = update_field('last_daily_reward_claimed', current_time('mysql'), $student_post_id);

    return $points_updated && $coins_updated && $last_claimed_updated;
}


/**
 * AJAX handler to fetch rewards.
 */
add_action('wp_ajax_fetch_rewards', 'fetch_rewards_ajax');
add_action('wp_ajax_nopriv_fetch_rewards', 'fetch_rewards_ajax');

function fetch_rewards_ajax()
{
    // Security check (if you use a nonce)
    // check_ajax_referer('your_rewards_nonce', 'nonce');

    // Get the student identifier (email) - You might need to adjust how you get this
    $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'cjaliya.sln2@gmail.com';
    $student_post_id = get_student_post_id_by_email($student_identifier);

    if (!$student_post_id || !function_exists('get_field')) {
        wp_send_json_error(['message' => 'Could not find student or ACF.']);
        return;
    }

    $rewards_html = '<div class="rewards-modal-content">';
    $rewards_html .= '<h3>Available Rewards</h3>';
    $rewards_html .= '<ul>';

    $reward_items = get_posts(array(
        'post_type' => 'reward-item',
        'posts_per_page' => -1,
    ));

    if ($reward_items) {
        foreach ($reward_items as $post) {
            $reward_name = get_the_title($post->ID);
            $reward_description = get_the_content(null, false, $post->ID);
            $required_coins = get_field('required_coins', $post->ID) ?: 0;
            $promotion_type = get_field('promotion_type', $post->ID) ?: '';
            $reload_value = get_field('reload_value', $post->ID) ?: '';
            $multiplication_type = get_field('multiplication_type', $post->ID) ?: '';
            $multiplication_factor = get_field('multiplication_factor', $post->ID) ?: '';
            $additional_type = get_field('additional_type', $post->ID) ?: '';
            $additional_reward = get_field('additional_reward', $post->ID) ?: '';
            $client_description = get_field('client_description', $post->ID) ?: '';
            $reward_image = get_the_post_thumbnail($post->ID, 'thumbnail');

            // Get the student's coin count
            $student_coins = get_field('coins', $student_post_id) ?: 0;

            if ($student_coins >= $required_coins) {
                $rewards_html .= '<li class="reward-item">';
                if ($reward_image) {
                    $rewards_html .= '<div class="reward-image">' . $reward_image . '</div>';
                }
                $rewards_html .= '<span class="reward-name">' . esc_html($reward_name) . '</span><br>';
                if ($client_description) {
                    $x_value = 0; // Default values for Points
                    $y_value = 0; // Default values for Coins

                    if ($promotion_type === 'addition') {
                        if ($additional_type === 'Points' || $additional_type === 'Both') {
                            $x_value = $additional_reward;
                        }
                        if ($additional_type === 'Coins' || $additional_type === 'Both') {
                            $y_value = $additional_reward;
                        }
                    }

                    $replaced_description = str_replace(
                        array('[X]', '[Y]'),
                        array(esc_html($x_value), esc_html($y_value)),
                        $client_description
                    );

                    $rewards_html .= '<span class="reward-client-description">' . wp_kses_post($replaced_description) . '</span><br>';
                }
                if ($reward_description) {
                    $rewards_html .= '<span class="reward-description">' . wp_kses_post($reward_description) . '</span><br>';
                }
                if ($required_coins > 0) {
                    $rewards_html .= '<span class="reward-cost">Requires: ' . esc_html($required_coins) . ' Coins</span><br>';
                }

                // Conditional rendering based on promotion_type
                if ($promotion_type === 'reload' && $reload_value) {
                    $rewards_html .= '<span class="reward-detail">Reload-Based: ' . esc_html($reload_value) . '</span><br>';
                } elseif ($promotion_type === 'multiplication' && $multiplication_type && $multiplication_factor) {
                    $rewards_html .= '<span class="reward-detail">Multiplication-Based (' . esc_html($multiplication_type) . '): ×' . esc_html($multiplication_factor) . '</span><br>';
                } elseif ($promotion_type === 'addition' && $additional_type && $additional_reward) {
                    $rewards_html .= '<span class="reward-detail">Addition-Based (' . esc_html($additional_type) . '): +' . esc_html($additional_reward) . '</span><br>';
                }

                $rewards_html .= '<button class="redeem-button" data-reward-id="' . esc_attr($post->ID) . '">Redeem</button>';
                $rewards_html .= '</li>';
            }
        }
    } else {
        $rewards_html .= '<li class="reward-item">No rewards available.</li>';
    }

    $rewards_html .= '</ul>';
    $rewards_html .= '</div>';

    wp_send_json_success(['rewards_html' => $rewards_html]);
}


