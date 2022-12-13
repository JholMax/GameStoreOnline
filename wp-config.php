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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
 define('FS_METHOD', 'direct');

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gso' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

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
define( 'AUTH_KEY',         'PjY@cm-c=>G<Tn-q|nw9cIE;D8MhBI0msKd?k){~b$F)^[[=u%@p?wI9jily6OLg' );
define( 'SECURE_AUTH_KEY',  'esRDf)=7!2~;.Hetg/{MLdasudanWJGvWO|u{@o0VV2[{/Yx<`*rlWO3Cy_>cYuA' );
define( 'LOGGED_IN_KEY',    '](t:x=Gs({?[.0D%6P?soch;[E$^f7fOD}asrQ4B;}O0aP^=%`prXIjSi6X9g&Vi' );
define( 'NONCE_KEY',        'XP|X3vG;8v*e2AQrR=D1z3OU2q15W#pDna>*-|[<?21$-_FzeIdM8mve}17]9+(:' );
define( 'AUTH_SALT',        't{lM%6BeY>Rxh~rUOm}qx3Y4!n@3iHV^u):RA0/,$}MZ9Sl}SlxGa>dkDJqR-Lxs' );
define( 'SECURE_AUTH_SALT', 'JgcW_:pQ#%< eMO5{_-LJ?w T8*[`<8K((~*uDMen8SjvZq.6%9$~O~s#~E*sU~u' );
define( 'LOGGED_IN_SALT',   'tVCSs%-<}[/CIMjl9k_=0X>Sk;C0%2G`?`,jMb P}3N,wpAGbnX^Y7:OVpglId^5' );
define( 'NONCE_SALT',       'eV(Su>e@$8o,Bb}/Ulgyb2,%qi&LOIVxk@{uA/MER$L(bbmKp?86;-QI7+D]~#[c' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
