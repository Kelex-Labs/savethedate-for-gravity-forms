<?php
/**
 * Opt-in per-site cleanup, preserving forms, entries and the other edition.
 *
 * @package SaveTheDate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( file_exists( WP_PLUGIN_DIR . '/savethedate-pro-for-gravity-forms/savethedate-pro-for-gravity-forms.php' ) ) {
	return;
}

/**
 * Remove only settings on an opted-in site.
 *
 * @since 1.0.0
 * @return void
 */
function stdgf_uninstall_site() {
	$settings = get_option( 'gravityformsaddon_savethedate_settings', array() );
	if ( empty( $settings['delete_data'] ) ) {
		return;
	}
	// Without GF APIs, preserve data for a later uninstall while GF is active.
	if ( ! class_exists( 'GFAPI' ) ) {
		return;
	}
	foreach ( array( false, true ) as $trashed ) {
		foreach ( GFAPI::get_forms( null, $trashed ) as $form ) {
			unset( $form['savethedate'] );
			GFAPI::update_form( $form );
		}
	}
	delete_option( 'gravityformsaddon_savethedate_settings' );
}

if ( is_multisite() ) {
	$stdgf_offset = 0;
	do {
		$stdgf_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $stdgf_offset,
			)
		);
		foreach ( $stdgf_sites as $stdgf_site_id ) {
			switch_to_blog( $stdgf_site_id );
			stdgf_uninstall_site();
			restore_current_blog();
		}
		$stdgf_offset += 100;
		$stdgf_count   = count( $stdgf_sites );
	} while ( 100 === $stdgf_count );
} else {
	stdgf_uninstall_site();
}
