<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress_reward' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'N1pun$' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'tciF0H08m6joII!@B{d8?=%1?KHR6xtIhQh@n$^ju+v_Y|CTtu]lBt%)<0oR/lpq' );
define( 'SECURE_AUTH_KEY',  'V!+XZ^c(s{# *=f}G5$_v8(MEQy/e<|;9)kN4.op/%MKlEGAx*Rf|w:0>kK0rR-d' );
define( 'LOGGED_IN_KEY',    'LSgaLz<}JKJ _~>wU=FENTeo_rsdU)$75DK+4+diq{uLdE+tclr:`P84yAch (ED' );
define( 'NONCE_KEY',        'U;`Te.)wp[OJ;}?tDuQA4XMU<x8jUjN{^(`g584E*uIu/ACQJ!NWat<<RekW>*vb' );
define( 'AUTH_SALT',        'Bpq/-)VI]v`wdK,cF$K-/iw`e=^U7A7 #2I^4Z.o0{fT##in.]s=yFnhwb*ctV,}' );
define( 'SECURE_AUTH_SALT', '9F@77bt8&la>4$3 DvkUQ9XG=Ap0Lp5RbC4oe9C~/ vl_,as*F7Ty*?}x5[.t<X~' );
define( 'LOGGED_IN_SALT',   'r=G)[WmmrE*&H)~0gA*}!B:nk=w|gf,{1b.`.r:yU@$:q!g3r)kJ} ^9;1WV+m%-' );
define( 'NONCE_SALT',       'Es/,ES&E`8^TJF,0dVK>aG}^b7|#184QB]~9bl{y=6Im/S4MuY^Z3U}na J?R*H+' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
