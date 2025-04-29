<?php
namespace Admin\Notifications;

// count endpoint (you already have)
add_action('wp_ajax_get_pending_redemptions_count', function() {
  if ( ! wp_verify_nonce($_POST['nonce'] ?? '', 'pending_redemptions_nonce') ) {
    wp_send_json_error();
  }
  wp_send_json_success([ 'count' => pp_get_pending_redemption_count() ]);
});

// items endpoint — return the pending redemptions details
add_action('wp_ajax_get_pending_redemptions_items', function() {
  if ( ! wp_verify_nonce($_POST['nonce'] ?? '', 'pending_redemptions_nonce') ) {
    wp_send_json_error();
  }
  $q = new WP_Query([
    'post_type'      => 'students_redeems',
    'post_status'    => 'publish',
    'meta_query'     => [[
      'key'     => 'status',
      'value'   => 'pending',
      'compare' => '=',
    ]],
    'posts_per_page' => 10, // limit or paginate as you like
  ]);

  $items = [];
  foreach( $q->posts as $post ) {
    // assume you store student ID in post meta 'student'
    $student_id  = get_post_meta($post->ID,'student',true);
    $student_name= get_the_title($student_id) ?: 'ID:' . $student_id;
    $reload_type = get_post_meta($post->ID,'reload_type',true);
    $items[] = [
      'student_name' => $student_name,
      'reload_type'  => $reload_type,
      'datetime'     => get_the_date('Y-m-d H:i:s',$post),
      'student_id'   => $student_id,
      'reward_id'    => $post->ID,
    ];
  }
  wp_send_json_success($items);
});
