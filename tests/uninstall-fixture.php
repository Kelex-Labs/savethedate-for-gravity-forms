<?php
require __DIR__ . '/bootstrap.php';
$stdgf_mode = $argv[1];
define( 'WP_UNINSTALL_PLUGIN', true );
define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/stdgf-uninstall-' . bin2hex( random_bytes( 6 ) ) );
$stdgf_form = array( 'id' => 12, 'title' => 'Keep this form', 'is_active' => false, 'is_trash' => false, 'savethedate' => array( 'enabled' => '1' ), 'another_addon' => array( 'keep' => 42 ) );
GFAPI::$forms = array( 12 => $stdgf_form, 13 => array_merge( $stdgf_form, array( 'id' => 13, 'is_trash' => true ) ) );
$GLOBALS['stdgf_options'] = array( 'gravityformsaddon_savethedate_settings' => array( 'delete_data' => 'preserve' === $stdgf_mode ? '0' : '1' ), 'unrelated_option' => 42 );
if ( 'companion' === $stdgf_mode ) {
	mkdir( WP_PLUGIN_DIR . '/savethedate-pro-for-gravity-forms', 0700, true );
	file_put_contents( WP_PLUGIN_DIR . '/savethedate-pro-for-gravity-forms/savethedate-pro-for-gravity-forms.php', '<?php' );
}
if ( 'multisite' === $stdgf_mode ) {
	$GLOBALS['stdgf_multisite'] = true;
	$GLOBALS['stdgf_blog_id'] = 1;
	$GLOBALS['stdgf_blog_data'] = array( 1 => array( 'forms' => GFAPI::$forms, 'options' => $GLOBALS['stdgf_options'] ), 2 => array( 'forms' => GFAPI::$forms, 'options' => array( 'gravityformsaddon_savethedate_settings' => array( 'delete_data' => '0' ) ) ) );
}
require dirname( __DIR__ ) . '/uninstall.php';
$stdgf_result = array( 'forms' => GFAPI::$forms, 'options' => $GLOBALS['stdgf_options'], 'sites' => $GLOBALS['stdgf_blog_data'] ?? array() );
if ( 'companion' === $stdgf_mode ) {
	unlink( WP_PLUGIN_DIR . '/savethedate-pro-for-gravity-forms/savethedate-pro-for-gravity-forms.php' );
	rmdir( WP_PLUGIN_DIR . '/savethedate-pro-for-gravity-forms' );
	rmdir( WP_PLUGIN_DIR );
}
echo json_encode( $stdgf_result );
