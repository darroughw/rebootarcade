<?php
/*
Plugin Name: Endurance Browser Cache
Description: Browser caching.
Version: 0.1
Author: Mike Hansen
Author URI: https://www.mikehansen.me/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Do not access file directly!
if ( ! defined( 'WPINC' ) ) { die; }

define( 'EBC_VERSION', 0.1 );


if ( ! class_exists( 'Endurance_Browser_Cache' ) ) {
	class Endurance_Browser_Cache {
		function __construct() {
			$this->hooks();
		}

		function hooks() {
			if ( $this->is_enabled() ) {
				add_filter( 'mod_rewrite_rules', array( $this, 'htaccess_contents' ) );
			}
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'status_link' ) );
		}

		function htaccess_contents( $rules ) {
			$default_files = array(
				'image/jpg'       => '1 year',
				'image/jpeg'      => '1 year',
				'image/gif'       => '1 year',
				'image/png'       => '1 year',
				'text/css'        => '1 month',
				'application/pdf' => '1 month',
				'text/javascript' => '1 month',
			);

			$file_types = wp_parse_args( get_option( 'ebc_filetype_expirations', array() ), $default_files );

			$additions = "<IfModule mod_expires.c>\n\tExpiresActive On\n\t";
			foreach ( $file_types as $file_type => $expires ) {
				$additions .= 'ExpiresByType ' . $file_type . ' "access plus ' . $expires . '"' . "\n\t";
			}

			$additions .= "ExpiresByType image/x-icon \"access plus 1 year\"\n\tExpiresDefault \"access plus 1 weeks\"\n</IfModule>\n";
			return $additions . $rules;
		}

		function is_enabled() {
			$cache_settings = get_option( 'mm_cache_settings' );
			if ( isset( $_GET['ebc_toggle'] ) ) {
				$valid_values = array( 'enabled', 'disabled' );
				if ( in_array( $_GET['ebc_toggle'], $valid_values ) ) {
					$cache_settings['browser'] = $_GET['ebc_toggle'];
					update_option( 'mm_cache_settings', $cache_settings );
					header( 'Location: ' . admin_url( 'plugins.php?plugin_status=mustuse' ) );
				}
			}
			if ( isset( $cache_settings['browser'] ) && 'disabled' == $cache_settings['browser'] ) {
				return false;
			} else {
				return true;
			}
		}

		function status_link( $links ) {
			if ( $this->is_enabled() ) {
				$links[] = '<a href="' . add_query_arg( array( 'ebc_toggle' => 'disabled' ) ) . '">Disable</a>';
			} else {
				$links[] = '<a href="' . add_query_arg( array( 'ebc_toggle' => 'enabled' ) ) . '">Enable</a>';
			}
			return $links;
		}
	}
	$ebc = new Endurance_Browser_Cache;
}
