<?php
namespace Admin\Notifications;
// Count pending redemptions
function pp_get_pending_redemption_count() {
  $q = new WP_Query([
    'post_type'      => 'students_redeems',
    'post_status'    => 'publish',
    'meta_query'     => [[
       'key'     => 'status',
       'value'   => 'pending',
       'compare' => '=',
    ]],
    'fields'         => 'ids',
    'posts_per_page' => -1,
  ]);
  return $q->found_posts;
}

// Add the bell + badge
// function pp_admin_bar_notification( $wp_admin_bar ) {
//   $count = pp_get_pending_redemption_count();
//   // Always output the node (so JS can attach click even if count=0)
//   $wp_admin_bar->add_node([
//     'id'     => 'pp-pending-redemptions',
//     'parent' => 'top-secondary',
//     'title'  => '<span class="pp-bell-icon"></span><span class="pp-count-badge">' . intval($count) . '</span>',
//     'href'   => '#',
//     'meta'   => [
//       'class' => 'pp-pending-redemptions-node',
//       'title' => __('View Pending Redemptions'),
//     ],
//   ]);
// }
// add_action('admin_bar_menu', 'pp_admin_bar_notification', 999);


class Admin_Notifications {
  public static function init() {
    add_action( 'admin_bar_menu', [ __CLASS__, 'render_bell' ], 999 );
    add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    require __DIR__ . '/ajax-handlers.php';
  }

  public static function enqueue_assets() {
    // only load in the admin bar context
    wp_enqueue_style(
      'pp-admin-notifications',
      plugin_dir_url( __DIR__ ) . '../assets/css/admin-notifications.css',
      [],
      '1.0'
    );
    wp_enqueue_script(
      'pp-admin-notifications',
      plugin_dir_url( __DIR__ ) . '../assets/js/admin-notifications.js',
      [ 'jquery' ],
      '1.0',
      true
    );
    wp_localize_script( 'pp-admin-notifications', 'PP_Notifications', [
      'ajax_url' => admin_url( 'admin-ajax.php' ),
      'nonce'    => wp_create_nonce( 'pending_redemptions_nonce' ),
    ] );
  }

  public static function render_bell( $wp_admin_bar ) {
    $count = self::get_pending_count();
    $wp_admin_bar->add_node( [
      'id'     => 'pp-pending-redemptions',
      'parent' => 'top-secondary',
      'title'  => '<span class="pp-bell-icon"></span><span class="pp-count-badge">' . esc_html( $count ) . '</span>',
      'href'   => '#',
      'meta'   => [
        'class' => 'pp-pending-redemptions-node',
        'title' => esc_attr__( 'View Pending Redemptions' ),
      ],
    ] );
  }

  private static function get_pending_count() {
    $q = new \WP_Query( [
      'post_type'      => 'students_redeems',
      'post_status'    => 'publish',
      'meta_query'     => [[
         'key'     => 'status',
         'value'   => 'pending',
         'compare' => '=',
      ]],
      'fields'         => 'ids',
      'posts_per_page' => -1,
    ] );
    return $q->found_posts;
  }
}

