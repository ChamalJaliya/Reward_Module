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
define( 'DB_NAME', 'differently_reward' );

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
define( 'AUTH_KEY',         '3S{xMrAH2]6F`9g`/Gq(8_# a 8W-jHJa` )bu[QmWiCRR{w2rP6X-ML{F~ZFh/e' );
define( 'SECURE_AUTH_KEY',  'NvHQt5+%6_jfFd!*Byyb5LcWm(T5f.ng3_ceTeC*r<R,Gx~rkRYd1_=Z{s@7YGC)' );
define( 'LOGGED_IN_KEY',    'k+r<(Z)K!f<}L?o4]C<km`Ju%.C(?2yO2xlzuVNfQHVfOM}Y4 y6YO~M]9Fv 63I' );
define( 'NONCE_KEY',        '`f.k-Hzp->9*G@<hT=8=m[jc1:=l3k;z-cXcT#dMLEu@?_Fmg%ed?t#65fd[ 2 [' );
define( 'AUTH_SALT',        'Kb+GA`S@a-4eA1Swm}9TajGJ72~F?RkPG)7|55_2:dBn(yMh.C)~Tps&`>3VD O@' );
define( 'SECURE_AUTH_SALT', 'bjr$V>wG@<1!^*1fe5?y:n#}n#7Z!]3`:WxkPf0NS$N.XF]oF][kvA%++;;DeHm+' );
define( 'LOGGED_IN_SALT',   'Zk0(ypwa(nX.+|u4ZMvG#~9,ae} Q_j -A:9?Ugd+pxYFQP$e80 90|z=RfAV@5E' );
define( 'NONCE_SALT',       '`NZ>Dok@qCG%].h4pJ[Y{dHx)z6C)$ge+zl-UnJgYAu8/EN*s2jhA3ZsikTp=Vl2' );

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
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
