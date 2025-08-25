<?php



/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */






define( 'DB_NAME', 'wp_ferredescuentos' );

/** Database username */
define( 'DB_USER', 'abaslealdbuser' );

/** Database password */
define( 'DB_PASSWORD', 'Db@sL3alPa$.2025' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',         'c9zyoa7ukygpyfuvlwhpdydrfb0q5b5ep2u9b8doxhu6ohgizqsltaijwaq5qjep' );
define( 'SECURE_AUTH_KEY',  'xs5tx6mdmdwi7mawpcs49zgozvgmbo9llc73vgrkbz4uqxy0t1r7f0euydisqflp' );
define( 'LOGGED_IN_KEY',    'cjzqnfhfb7tpeqklyl3hsus06sxcptdcwvjfqjstzflqzrbud1g6bwydhmyntw5m' );
define( 'NONCE_KEY',        'cuz8zrbuqxcsi1bznqk8ufsi2kf2dookp65bdmtjp712z1tsfbjor2m1zrkb6je5' );
define( 'AUTH_SALT',        '1ocgjut2fp4cstadcb3lqwvribqlkhx6n6k7nx9ldtdgounlybmg2or3ybdlgmyo' );
define( 'SECURE_AUTH_SALT', 'zf6zmwpattp6vpjswnfknblasfvjvpkdllg5hnjqt7sehegdprhzdhwawy80xz9g' );
define( 'LOGGED_IN_SALT',   '08f273retifmdbqm5dwntugchgurwmbtqgm53qbax2grglaoe4q9f50xt0rizq3x' );
define( 'NONCE_SALT',       'gmiv0xiiw6jmdqsyqylfmmg16jljrafmhsnoymnqwrekmuw3s5he9uq1miki1e3i' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpvu_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
ini_set('display_errors','Off');
ini_set('error_reporting', E_ALL );
define( 'WP_DEBUG_DISPLAY', false);
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );


/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';