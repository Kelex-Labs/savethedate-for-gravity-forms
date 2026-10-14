<?php
/**
 * Plugin Name: SaveTheDate for Gravity Forms
 * Plugin URI: https://github.com/Kelex-Labs/savethedate-for-gravity-forms
 * Description: Add calendar buttons to fixed event confirmations and emails in Gravity Forms.
 * Version: 1.0.2
 * Author: Kelex Labs
 * Author URI: https://github.com/Kelex-Labs
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: savethedate-for-gravity-forms
 * Domain Path: /languages
 *
 * @package SaveTheDate
 */

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'stdgf_bootstrap', 0 );

/** Boot the active edition after all plugin files have loaded. */
function stdgf_bootstrap(): void {
	if ( defined( 'STDGF_PRO_VERSION' ) ) {
		return;
	}
	define( 'STDGF_VERSION', '1.0.2' );
	define( 'STDGF_FILE', __FILE__ );
	if ( version_compare( PHP_VERSION, '7.4', '<' ) || version_compare( get_bloginfo( 'version' ), '6.2', '<' ) ) {
		add_action( 'admin_notices', 'stdgf_dependency_notice' );
		return;
	}
	require_once __DIR__ . '/vendor/autoload.php';
	add_action( 'gform_loaded', 'stdgf_register_addon', 5 );
	if ( did_action( 'gform_loaded' ) ) {
		stdgf_register_addon();
	}
	add_action( 'init', 'stdgf_check_dependency' );
}

/** Register once Gravity Forms can load its add-on framework. */
function stdgf_register_addon(): void {
	if ( ! class_exists( 'GFForms' ) || version_compare( GFForms::$version, '2.7', '<' ) ) {
		return;
	}
	GFForms::include_addon_framework();
	GFAddOn::register( '\\SaveTheDate\\AddOn' );
	SaveTheDate\AddOn::get_instance();
}

/** Delay dependency checks until Gravity Forms has finished loading. */
function stdgf_check_dependency(): void {
	if ( ! class_exists( 'GFForms' ) || version_compare( GFForms::$version, '2.7', '<' ) ) {
		add_action( 'admin_notices', 'stdgf_dependency_notice' );
	}
}

/** Display a dependency notice to site administrators. */
function stdgf_dependency_notice(): void {
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'SaveTheDate requires WordPress 6.2+, PHP 7.4+ and an active licensed copy of Gravity Forms 2.7+.', 'savethedate-for-gravity-forms' ) . '</p></div>';
	}
}
