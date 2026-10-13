<?php
/** Dependency-free checks for offline development, separate from PHPUnit. */
require __DIR__ . '/bootstrap.php';
$stdgf_checks = 0;
function stdgf_check( $valid, $message ) {
	global $stdgf_checks;
	if ( ! $valid ) { throw new RuntimeException( $message ); }
	++$stdgf_checks;
}
$stdgf_settings = array( 'enabled' => '1', 'title' => 'Workshop', 'start_date' => '2027-07-01', 'start_time' => '10:00', 'end_date' => '2027-07-01', 'end_time' => '11:30', 'timezone' => 'Europe/Malta', 'description' => "Hello, world; café\n日本語" );
$stdgf_factory = new SaveTheDate\Calendar\EventFactory();
$stdgf_event = $stdgf_factory->create( $stdgf_settings, 12, 'Form', 'UTC', 'site' );
stdgf_check( '20270701T080000Z' === $stdgf_event->date( $stdgf_event->start ), 'Summer UTC conversion.' );
foreach ( array( 'Europe/Malta' => '080000', 'America/New_York' => '140000', 'Australia/Sydney' => '000000', 'Asia/Kolkata' => '043000' ) as $zone => $expected ) {
	$event = $stdgf_factory->create( array_merge( $stdgf_settings, array( 'timezone' => $zone ) ), 12, 'Form', 'UTC', 'site' );
	stdgf_check( substr( $event->date( $event->start ), -7 ) === $expected . 'Z', 'Timezone ' . $zone );
}
$stdgf_day = $stdgf_factory->create( array_merge( $stdgf_settings, array( 'all_day' => '1' ) ), 12, 'Form', 'UTC', 'site' );
stdgf_check( '20270702' === $stdgf_day->date( $stdgf_day->end ), 'Inclusive all-day conversion.' );
$stdgf_builder = new SaveTheDate\Calendar\IcsBuilder();
$stdgf_line = 'DESCRIPTION:' . str_repeat( 'ħü日本語🙂', 100 );
$stdgf_folded = $stdgf_builder->fold( $stdgf_line );
stdgf_check( $stdgf_line === str_replace( "\r\n ", '', $stdgf_folded ), 'Lossless folding.' );
foreach ( explode( "\r\n", $stdgf_folded ) as $part ) { stdgf_check( strlen( $part ) <= 75 && 1 === preg_match( '//u', $part ), 'UTF-8 octet limit.' ); }
stdgf_check( 'a\\,b\\;c\\\\d\\ne' === $stdgf_builder->escape( "a,b;c\\d\r\ne\x01" ), 'RFC 5545 escaping.' );
foreach ( array( array( 'start_date' => '' ), array( 'start_date' => '2027-02-30' ), array( 'end_time' => '09:00' ), array( 'end_time' => '10:00' ) ) as $changes ) {
	$failed = false;
	try { $stdgf_factory->create( array_merge( $stdgf_settings, $changes ), 12, 'Form', 'UTC', 'site' ); } catch ( InvalidArgumentException $exception ) { $failed = true; }
	stdgf_check( $failed, 'Invalid event rejection.' );
}
$stdgf_form = array( 'id' => 12, 'title' => 'Workshop', 'is_active' => true, 'is_trash' => false, 'savethedate' => $stdgf_settings );
GFAPI::$forms[12] = $stdgf_form;
add_filter( 'stdgf_now', function () { return new DateTimeImmutable( '2027-07-01T09:29:40.900000Z' ); } );
$stdgf_request = new WP_REST_Request( 'GET', '/savethedate/v1/ics/form/12' ); $stdgf_request->set_param( 'form_id', 12 );
$stdgf_route = new SaveTheDate\Http\IcsRoute();
$stdgf_response = $stdgf_route->response( $stdgf_request );
stdgf_check( 'public, max-age=19, must-revalidate' === $stdgf_response->get_headers()['Cache-Control'], 'Cache never exceeds fractional lifetime.' );
ob_start(); $stdgf_served = $stdgf_route->serve( false, $stdgf_response, $stdgf_request, new WP_REST_Server() ); $stdgf_body = ob_get_clean();
stdgf_check( $stdgf_served && $stdgf_response->get_data() === $stdgf_body, 'Raw calendar response.' );
$stdgf_addon = SaveTheDate\AddOn::get_instance();
foreach ( array( true, false ) as $ajax ) {
	$html = $stdgf_addon->confirmation( 'Thanks!', $stdgf_form, array(), $ajax );
	stdgf_check( 1 === substr_count( $html, 'role="group"' ), 'Confirmation buttons.' );
	stdgf_check( $html === $stdgf_addon->confirmation( $html, $stdgf_form, array(), $ajax ), 'Duplicate prevention.' );
}
$stdgf_notification = $stdgf_addon->notification( array( 'message' => '{save_the_date}', 'message_format' => 'multipart' ), $stdgf_form, array() );
$stdgf_html = $stdgf_addon->merge_tag( $stdgf_notification['message'], $stdgf_form, array(), false, false, false, 'html' );
$stdgf_text = $stdgf_addon->merge_tag( $stdgf_notification['message'], $stdgf_form, array(), false, false, false, 'text' );
stdgf_check( false !== strpos( $stdgf_html, '<table' ) && false === strpos( $stdgf_text, '<' ), 'Multipart email variants.' );
GFAPI::$forms[12]['savethedate']['unknown'] = 42;
$stdgf_addon->save_form_settings( $stdgf_form, $stdgf_settings );
stdgf_check( 42 === GFAPI::$forms[12]['savethedate']['unknown'], 'Unknown settings preserved.' );
foreach ( array( 'disabled', 'inactive', 'trashed', 'invalid', 'ended', 'missing' ) as $case ) {
	GFAPI::$forms[12] = $stdgf_form;
	if ( 'disabled' === $case ) { GFAPI::$forms[12]['savethedate']['enabled'] = '0'; }
	if ( 'inactive' === $case ) { GFAPI::$forms[12]['is_active'] = false; }
	if ( 'trashed' === $case ) { GFAPI::$forms[12]['is_trash'] = true; }
	if ( 'invalid' === $case ) { GFAPI::$forms[12]['savethedate']['start_date'] = 'bad'; }
	if ( 'ended' === $case ) { GFAPI::$forms[12]['savethedate']['end_time'] = '10:01'; }
	if ( 'missing' === $case ) { unset( GFAPI::$forms[12] ); }
	stdgf_check( $stdgf_route->response( $stdgf_request ) instanceof WP_Error, '404 gate: ' . $case );
}
fwrite( STDOUT, "PASS: {$stdgf_checks} offline smoke checks. WP/GF doubles used; live QA remains separate.\n" );
