<?php
// Count pending reload‐type redemptions and return the actual WP_Post objects
function get_pending_reload_requests() {
    $args = [
        'post_type'      => 'students_redeems',
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => 'status',
                'value'   => 'pending',
                'compare' => '=',
            ],
            [
                'key'     => 'promotion_type',
                'value'   => 'reload',
                'compare' => '=',
                'type'    => 'CHAR',
                'relation' => 'AND',
                // we’ll actually need a JOIN on the reward post; see below
            ],
        ],
        'posts_per_page' => -1,
    ];
    // since promotion_type is on the reward post, we can filter in PHP below
    $query = new WP_Query(array_merge($args, ['meta_query' => [
        [
            'key'     => 'status',
            'value'   => 'pending',
            'compare' => '=',
        ],
    ]]));
    // now only keep those whose linked reward_item has promotion_type=reload
    $results = [];
    foreach ($query->posts as $redeem) {
        $raw = get_field('reward_item', $redeem->ID);
        if (!$raw) continue;
        // normalize reward ID
        if (is_array($raw)) {
            $raw = reset($raw);
        }
        $rid = is_object($raw) ? $raw->ID : intval($raw);
        if (get_field('promotion_type', $rid) === 'reload') {
            $results[] = $redeem;
        }
    }
    return $results;
}

function display_pending_notification_icon( $wp_admin_bar ) {
    $requests      = get_pending_reload_requests();
    $pending_count = count( $requests );

    // 1) Bell + badge parent
    $wp_admin_bar->add_node([
        'id'     => 'pending-redemptions',
        'parent' => 'top-secondary',
        'title'  => '<span class="pp-bell-icon"></span>'
                   . ( $pending_count ? '<span class="pp-count-badge">' . $pending_count . '</span>' : '' ),
        'meta'   => [
            'title' => 'View Pending Redemptions',
            'class' => 'pending-redemptions-notification menupop',
            'html'  => true,
        ],
    ]);

    // 2) Child items — slice to latest 5, but all point to the table
    if ( $pending_count ) {
        $list_url = admin_url( 'edit.php?post_type=students_redeems' );
        foreach ( array_slice( $requests, 0, 5 ) as $redeem ) {
            // normalize student
            $student = get_field('student', $redeem->ID);
            if ( is_array( $student ) ) {
                $student = reset( $student );
            }
            $sid   = is_object( $student ) ? $student->ID : intval( $student );

            $first = $sid ? get_field('first_name', $sid) : '';
            $last = $sid ? get_field('last_name', $sid) : '';
            $name  = trim( "$first $last");
            if (!$name) {
                $name  = $sid ? get_the_title( $sid )    : '—';
            }
            // $name  = $sid ? get_the_title( $sid )    : '—';
            
            $email = $sid ? get_field('email', $sid) : '—';
            $time  = get_field('claimed_timestamp', $redeem->ID);

            // build the label HTML
            $label = sprintf(
                '<strong>%1$s</strong><br>%2$s<br><time datetime="%3$s">%4$s</time>',
                esc_html( $name ),
                esc_html( $email ),
                esc_attr( $time ),
                esc_html( date('Y-m-d H:i', strtotime($time)) )
            );

            $wp_admin_bar->add_node([
                'id'     => 'pending-redemption-' . $redeem->ID,
                'parent' => 'pending-redemptions',
                'title'  => $label,
                'href'   => $list_url,            // ← everyone goes to the table
                'meta'   => [ 'html' => true ],
            ]);
        }
    }
}
add_action('admin_bar_menu', 'display_pending_notification_icon', 100);


// Styles to make the submenu appear on hover
function add_pending_notification_styles() {
    echo '<style>
    /* container styling */
    #wpadminbar #wp-admin-bar-pending-redemptions .ab-item {
        position: relative;
        padding-left: 0;
        color: #fff;
    }

    /* bell icon */
    #wpadminbar .pp-bell-icon:before {
        content: "\\f488";      /* dashicons-bell */
        font-family: Dashicons;
        font-size: 18px;
        vertical-align: middle;
    }

    /* red count badge */
    #wpadminbar .pp-count-badge {
        background: #d63638;
        color: #fff;
        font-size: 10px;
        font-weight: bold;
        border-radius: 10px;
        line-height: 1;
        padding: 2px 5px;
        position: absolute;
        top: 3px;
        right: 3px;
    }

    /* force the submenu open on hover */
    #wpadminbar #wp-admin-bar-pending-redemptions.menupop:hover > .ab-submenu {
        display: block !important;
    }

    /* submenu panel */
    #wpadminbar #wp-admin-bar-pending-redemptions .ab-submenu {
        background: #fff;
        border: 1px solid #ccc;
        width: 280px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    #wpadminbar #wp-admin-bar-pending-redemptions .ab-submenu li > a {
        display: block;
        padding: 8px 12px;
        font-size: 13px;
        color: #333; /* Default text color for the link content */
        line-height: 1.4;
        text-decoration: none; /* Remove underline from the whole link */
    }

    /* Hover state for the link - applies background to whole item */
    #wpadminbar #wp-admin-bar-pending-redemptions .ab-submenu li > a:hover {
        background-color: #f0f0f1; /* Light grey background on hover */
        color: #000; /* Optional: darken text slightly on hover */
    }

    /* --- Style the name (strong tag) to look like normal text --- */
    #wpadminbar #wp-admin-bar-pending-redemptions .ab-submenu li > a strong {
        color: inherit; /* Inherit color from the parent <a> tag */
        font-weight: bold; /* Ensure it stays bold */
        text-decoration: none; /* Ensure no underline */
        color: #333;
    }
    /* --- End style for name --- */

    #wpadminbar #wp-admin-bar-pending-redemptions .ab-submenu li > a time {
        display: block;
        font-size: 11px;
        color: #666;
        margin-top: 4px;
    }
    </style>';
}
add_action('admin_head', 'add_pending_notification_styles');






// Here's the plugins/points-plus/includes/admin/admin-notifications/admin-bar.php:

// Here's the plugins/points-plus/includes/admin/admin-notifications/ajax-handlers.php:

// Here's the plugins/points-plus/includes/assets/js/admin-notifications/admin-notifications.js:

// Here's the plugins/points-plus/includes/assets/css/admin-notifications/admin-notifications.css:

// Here's the part in the points-plus.php:
