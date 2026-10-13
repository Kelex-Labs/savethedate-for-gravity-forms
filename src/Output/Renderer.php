<?php
/**
 * Accessible page and email output.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Output;

use SaveTheDate\Calendar\LinkBuilder;
use SaveTheDate\EventService;
defined( 'ABSPATH' ) || exit;

/** Renders public fixed events only. */
final class Renderer {
	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form Form.
	 * @param string               $context Context.
	 * @return string
	 */
	public function render( array $form, string $context = 'web' ): string {
		$event = ( new EventService() )->event( $form );
		if ( null === $event ) {
			return '';
		}
		$urls   = ( new LinkBuilder() )->build( $event, rest_url( 'savethedate/v1/ics/form/' . (int) $form['id'] ) );
		$labels = array(
			'google'    => __( 'Google Calendar', 'savethedate-for-gravity-forms' ),
			'outlook'   => __( 'Outlook.com', 'savethedate-for-gravity-forms' ),
			'office365' => __( 'Microsoft 365', 'savethedate-for-gravity-forms' ),
			'yahoo'     => __( 'Yahoo Calendar', 'savethedate-for-gravity-forms' ),
			'ics'       => __( 'Apple / Outlook (.ics)', 'savethedate-for-gravity-forms' ),
		);
		foreach ( $urls as $provider => $url ) {
			$urls[ $provider ] = apply_filters( 'stdgf_link_url', $url, $provider, $event );
		}
		if ( 'text' === $context ) {
			$lines = array();
			foreach ( $urls as $provider => $url ) {
				$lines[] = $labels[ $provider ] . ': ' . esc_url_raw( $url );
			}
			return implode( "\n", $lines );
		}
		if ( 'email' === $context ) {
			$html = '<table role="presentation"><tr><td><p>' . esc_html__( 'Add this event to your calendar', 'savethedate-for-gravity-forms' ) . '</p>';
			foreach ( $urls as $provider => $url ) {
				$html .= '<p><a href="' . esc_url( $url ) . '">' . esc_html( $labels[ $provider ] ) . '</a></p>';
			}
			return $html . '</td></tr></table>';
		}
		$settings = \SaveTheDate\AddOn::get_instance()->get_plugin_settings();
		$style    = isset( $settings['style'] ) && 'outline' === $settings['style'] ? 'outline' : 'solid';
		$accent   = sanitize_hex_color( $settings['accent'] ?? '#2455a4' );
		$accent   = $accent ? $accent : '#2455a4';
		// Choose a readable text color using the accent's relative luminance.
		$channels = array();
		$hex      = ltrim( $accent, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		foreach ( array( 0, 2, 4 ) as $offset ) {
			$channel    = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			$channels[] = $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
		}
		$luminance = $channels[0] * 0.2126 + $channels[1] * 0.7152 + $channels[2] * 0.0722;
		$text      = $luminance > 0.179 ? '#000000' : '#ffffff';
		$this->enqueue();
		$template = locate_template( 'savethedate/buttons.php' );
		$template = $template ? $template : dirname( STDGF_FILE ) . '/templates/buttons.php';
		$template = apply_filters( 'stdgf_template_path', $template, $form );
		ob_start();
		do_action( 'stdgf_before_buttons', $event, $form );
		include $template;
		do_action( 'stdgf_after_buttons', $event, $form );
		$html = (string) ob_get_clean();
		return wp_kses( apply_filters( 'stdgf_buttons_html', $html, $event, $form, $context ), $this->allowed_html() );
	}

	/**
	 * Load styles for an event form before a possible AJAX confirmation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue(): void {
		wp_enqueue_style( 'stdgf-frontend', plugins_url( 'assets/css/frontend.css', STDGF_FILE ), array(), STDGF_VERSION );
	}

	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @return array<string, array<string, bool>>
	 */
	private function allowed_html(): array {
		return array(
			'div'  => array(
				'class'      => true,
				'role'       => true,
				'aria-label' => true,
				'style'      => true,
			),
			'p'    => array( 'class' => true ),
			'span' => array( 'class' => true ),
			'a'    => array(
				'class'    => true,
				'href'     => true,
				'target'   => true,
				'rel'      => true,
				'download' => true,
			),
		);
	}
}
