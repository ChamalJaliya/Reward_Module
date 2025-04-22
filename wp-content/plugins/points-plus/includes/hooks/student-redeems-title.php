<?php
// Automatically generate post title for Student Redeem

add_action('acf/save_post', 'points_plus_maybe_set_flag', 10);
function points_plus_maybe_set_flag($post_id) {
    if (get_post_type($post_id) === 'students_redeems') {
        set_transient("points_plus_needs_title_$post_id", true, 60);
    }
}

add_action('wp_after_insert_post', 'points_plus_set_auto_title_after_save', 20, 4);
function points_plus_set_auto_title_after_save($post_id, $post, $update, $postarr) {
    if ($post->post_type !== 'students_redeems') return;

    if (!get_transient("points_plus_needs_title_$post_id")) return;
    delete_transient("points_plus_needs_title_$post_id");

    // Get student and reward (assume they return post IDs)
    $student = get_field('student', $post_id);
    $reward  = get_field('reward_item', $post_id);
    $claimed = get_field('claimed_timestamp', $post_id);

    $student_id = is_numeric($student) ? $student : ($student['ID'] ?? 0);
    $reward_id  = is_numeric($reward) ? $reward : ($reward['ID'] ?? 0);

    // Get titles (only if IDs are valid)
    $student_title = $student_id ? get_the_title($student_id) : '';
    $reward_title  = $reward_id ? get_the_title($reward_id) : '';

    if ($student_title && $reward_title && $claimed) {
        $title = $student_title . ' - ' . $reward_title . ' - ' . date('Y-m-d', strtotime($claimed));
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => $title,
            'post_name'  => sanitize_title($title), // Optional: updates slug || Useful if the permalink is visible or used anywhere.
        ]);
    }
}
