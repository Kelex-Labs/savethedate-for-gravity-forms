<?php
/** Run with WP-CLI on the explicitly designated disposable playground. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'GFAPI' ) ) { exit( 1 ); }
$stdgf_checks = array();
function stdgf_qa_assert( $pass, $label ) {
	if ( ! $pass ) { WP_CLI::error( $label ); }
	$GLOBALS['stdgf_checks'][] = $label;
}
$stdgf_form = GFAPI::get_form( 1 );
$stdgf_addon = SaveTheDate\AddOn::get_instance();
$stdgf_renderer = new SaveTheDate\Output\Renderer();
$stdgf_html = $stdgf_renderer->render( $stdgf_form );
stdgf_qa_assert( 1 === substr_count( $stdgf_html, 'role="group"' ), 'One accessible group after real WordPress sanitization' );
stdgf_qa_assert( false !== strpos( $stdgf_html, '--stdgf-accent:' ), 'WordPress preserves safe CSS custom properties' );
stdgf_qa_assert( 5 === substr_count( $stdgf_html, '<a ' ), 'Five providers render through real WordPress' );
$stdgf_entries = GFAPI::get_entries( 1, array( 'status' => 'active' ), null, array( 'offset' => 0, 'page_size' => 1 ) );
$stdgf_entry = $stdgf_entries[0];
$stdgf_notification = apply_filters( 'gform_notification', array( 'message' => 'Registered {save_the_date}' ), $stdgf_form, $stdgf_entry );
$stdgf_email = GFCommon::replace_variables( $stdgf_notification['message'], $stdgf_form, $stdgf_entry, false, false, false, 'html' );
$stdgf_text = GFCommon::replace_variables( $stdgf_notification['message'], $stdgf_form, $stdgf_entry, false, false, false, 'text' );
stdgf_qa_assert( false !== strpos( $stdgf_email, '<table role="presentation">' ), 'Real GF merge replacement produces email HTML' );
stdgf_qa_assert( false === strpos( $stdgf_text, '<' ) && false !== strpos( $stdgf_text, 'Google Calendar: https://' ), 'Real GF multipart text replacement has labeled URLs' );
$stdgf_saved = $stdgf_form;
register_shutdown_function( function () use ( $stdgf_saved ) {
	GFAPI::update_form( $stdgf_saved );
	GFAPI::update_form_property( 1, 'is_active', $stdgf_saved['is_active'] );
	GFAPI::update_form_property( 1, 'is_trash', $stdgf_saved['is_trash'] );
} );
$stdgf_form['savethedate']['stdgf_qa_unknown'] = array( 'value' => 42 );
GFAPI::update_form( $stdgf_form );
$stdgf_addon->save_form_settings( $stdgf_form, array( 'enabled' => '0' ) );
$stdgf_after = GFAPI::get_form( 1 );
stdgf_qa_assert( 42 === $stdgf_after['savethedate']['stdgf_qa_unknown']['value'], 'GF save preserves unknown settings' );
stdgf_qa_assert( $stdgf_saved['savethedate']['start_date'] === $stdgf_after['savethedate']['start_date'], 'Disabling preserves event dates' );
GFAPI::update_form( $stdgf_saved );
$stdgf_route = new SaveTheDate\Http\IcsRoute();
$stdgf_request = new WP_REST_Request( 'GET', '/savethedate/v1/ics/form/1' );
$stdgf_request->set_param( 'form_id', 1 );
foreach ( array( 'inactive', 'trashed', 'disabled', 'invalid', 'ended' ) as $stdgf_case ) {
	$stdgf_changed = $stdgf_saved;
	if ( 'inactive' === $stdgf_case ) { $stdgf_changed['is_active'] = false; }
	if ( 'trashed' === $stdgf_case ) { $stdgf_changed['is_trash'] = true; }
	if ( 'disabled' === $stdgf_case ) { $stdgf_changed['savethedate']['enabled'] = '0'; }
	if ( 'invalid' === $stdgf_case ) { $stdgf_changed['savethedate']['start_date'] = 'bad'; }
	if ( 'ended' === $stdgf_case ) { $stdgf_changed['savethedate']['start_date'] = '2020-01-01'; $stdgf_changed['savethedate']['end_date'] = '2020-01-01'; }
	GFAPI::update_form( $stdgf_changed );
	GFAPI::update_form_property( 1, 'is_trash', $stdgf_changed['is_trash'] );
	$stdgf_response = $stdgf_route->response( $stdgf_request );
	stdgf_qa_assert( is_wp_error( $stdgf_response ) && 404 === $stdgf_response->get_error_data()['status'], '404 for ' . $stdgf_case . ' event through real GFAPI' );
}
GFAPI::update_form( $stdgf_saved );
GFAPI::update_form_property( 1, 'is_trash', $stdgf_saved['is_trash'] );
stdgf_qa_assert( false !== strpos( do_shortcode( '[savethedate form="1"]' ), 'stdgf-buttons' ), 'Real WordPress shortcode renders' );
$stdgf_all_day = $stdgf_saved;
$stdgf_all_day['savethedate']['all_day'] = '1';
GFAPI::update_form( $stdgf_all_day );
$stdgf_response = $stdgf_route->response( $stdgf_request );
stdgf_qa_assert( false !== strpos( $stdgf_response->get_data(), 'DTEND;VALUE=DATE:20270702' ), 'Real REST all-day output has exclusive end' );
GFAPI::update_form( $stdgf_saved );
WP_CLI::line( wp_json_encode( array( 'passed' => count( $GLOBALS['stdgf_checks'] ), 'checks' => $GLOBALS['stdgf_checks'] ) ) );
