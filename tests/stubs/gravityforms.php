<?php
/** Minimal developer-owned signatures verified against licensed Herd GF 3.1.3.2. */
class GFAddOn {
	protected $_version;
	protected $_min_gravityforms_version;
	protected $_slug;
	protected $_path;
	protected $_full_path;
	protected $_title;
	protected $_short_title;
	protected $_capabilities_form_settings;
	protected $_capabilities_settings_page;
	protected $_capabilities_uninstall;
	public static $global_settings = array();
	public static $posted_settings = array();
	public static $registered = array();
	public function __construct() {}
	public function init() {}
	public static function register( $class ) { self::$registered[] = $class; }
	public function get_plugin_settings() { return self::$global_settings; }
	public function get_posted_settings() { return self::$posted_settings; }
	public function update_plugin_settings( $settings ) { self::$global_settings = $settings; }
	public function save_form_settings( $form, $settings ) { $form['savethedate'] = $settings; GFAPI::$forms[$form['id']] = $form; return true; }
}
class GFForms {
	public static $version = '3.1.3.2';
	public static function include_addon_framework() {}
}
class GFAPI {
	public static $forms = array();
	public static function get_form( $id ) { return self::$forms[$id] ?? false; }
	public static function get_forms( $active = true, $trash = false ) { return array_filter( self::$forms, function ( $form ) use ( $active, $trash ) { return ( null === $active || (bool) $form['is_active'] === $active ) && (bool) ( $form['is_trash'] ?? false ) === $trash; } ); }
	public static function update_form( $form ) { self::$forms[$form['id']] = $form; return true; }
}
