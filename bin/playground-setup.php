<?php
/** WP-CLI fixture setup for a disposable local WordPress playground. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'GFAPI' ) ) {
	exit( 1 );
}
$stdgf_data = json_decode( file_get_contents( dirname( __DIR__ ) . '/examples/sample-form.json' ), true );
$stdgf_form = $stdgf_data['0'];
$stdgf_form['title'] = 'SaveTheDate QA Workshop';
// Browser tests must never send external mail.
$stdgf_form['notifications'] = array();
$stdgf_form_id = 0;
foreach ( GFAPI::get_forms() as $stdgf_existing ) {
	if ( $stdgf_form['title'] === $stdgf_existing['title'] ) {
		$stdgf_form_id = (int) $stdgf_existing['id'];
		break;
	}
}
if ( ! $stdgf_form_id ) {
	$stdgf_form_id = GFAPI::add_form( $stdgf_form );
}
if ( is_wp_error( $stdgf_form_id ) ) {
	WP_CLI::error( $stdgf_form_id->get_error_message() );
}
$stdgf_pages = array();
foreach ( array( 'regular' => false, 'ajax' => true ) as $stdgf_mode => $stdgf_ajax ) {
	$stdgf_slug = 'savethedate-qa-' . $stdgf_mode;
	$stdgf_existing_page = get_page_by_path( $stdgf_slug );
	$stdgf_page_id = wp_insert_post( array(
		'ID' => $stdgf_existing_page ? $stdgf_existing_page->ID : 0,
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_name' => $stdgf_slug,
		'post_title' => 'SaveTheDate QA ' . ucfirst( $stdgf_mode ),
		'post_content' => '[gravityform id="' . $stdgf_form_id . '" title="false" description="false" ajax="' . ( $stdgf_ajax ? 'true' : 'false' ) . '"]',
	), true );
	if ( is_wp_error( $stdgf_page_id ) ) {
		WP_CLI::error( $stdgf_page_id->get_error_message() );
	}
	$stdgf_pages[ $stdgf_mode ] = get_permalink( $stdgf_page_id );
}
WP_CLI::line( wp_json_encode( array( 'form_id' => $stdgf_form_id, 'pages' => $stdgf_pages ) ) );
