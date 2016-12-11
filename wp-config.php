<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'reboot');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'xG?&Yj^BsFp@)G=mA_GN;DoJVTMwOw=>JKqhV^Kq}ecjm>-YHs[!qQmyD-{]ysV})MtmnyB[AA{@Wzw{dSWEKTE@-xnbR$r(BuZ*!=oVY_UkSq;VLqcAzvc^ZjWxa{;k');
define('SECURE_AUTH_KEY', 'AHkH}HnsLf$NS<|xeF_cbPp&Z!thdsRrEuTzz|SE^tm_n-vM]*ZgA|IhPh<QJrDIJJb(J]_sj=vvFI_hXIjxmJXzP@;++gZ/Tl>V&K>$c&dw_%oM>;F=/OMu<euH&LY@');
define('LOGGED_IN_KEY', 'N_Req?%I[KnOXZu-uxxFDP]&ISwPqeVrPsKZcNHMkm)X&>{HI$MHgLnHq@Q+NDNOXq(dzd|tOvz+DN!Ey>Is-jS{/(+I%cl<zi{>[o*Iz]ii}RSii)M?Q!UPYPMGKkuI');
define('NONCE_KEY', '/<icBzIYQoT}yLzTwBDNdhXIHt}JB(q-RimKT*s(v=>^)i^-UtLB@<]mh{bL_hf=a;V)NA[ufyKZtBs_Mdf=DV{kWZYxhVz^Ofw]lb$C+{DA<a[(hw])k]L&G|Jeq*tg');
define('AUTH_SALT', 'MVmEaHhuIYzRB?O(<!-RKdQ($@OVdPPl|>v=D}fyyEu=_hWon{=KEHIL*q%bji$+_=K%WLHNk/S/&!}<N&dvRBdY^NFXG_^+<UGW&D*b%Mj%/eJe-VAa&C%-[RBM}Ise');
define('SECURE_AUTH_SALT', '@Ha&Rf>nVQ{=%]DI/[<H=Ymezio%?*So-aXSgLRA;fkJ>|uEJZh!-FB>DwWQ|A_JszdOfJF*OunhgmuvwfSG!BZref>Qo?jvTs-e^xo-y*=Q^B>_wRojFa@Qb*_WzeMm');
define('LOGGED_IN_SALT', 'PTLY$OKd(rUv[nNHlcV>pyb!tlwBmqu_+lqW?VnG^fJpota^B;a<FyRGDY<%}JDYMkj}&[wvUYiDxy(s@[EU$E>d=Jfo@nUR);(v]wk[TMJ<ypsH<|A</Oqm/)}-Zw?]');
define('NONCE_SALT', 'F)[vML<Cj*H?zzzH{PYP)ZQLVRP|JNc&BF?OWl!|BAe$Z[<Cdl?z(J}j|Ui)+ht+nsZBbiH&G/In_Gei(?[ID{LCw(za*n<}G>VI{C*Po$Xza@AT*tC<HthYTAcDujT(');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_uobi_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
