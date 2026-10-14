<?php
/**
 * Accessible page and email output.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Output;

use SaveTheDate\Calendar\LinkBuilder;
use SaveTheDate\Calendar\Event;
use SaveTheDate\EventService;
defined( 'ABSPATH' ) || exit;

/** Renders public fixed events only. */
final class Renderer {
	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>      $form Form.
	 * @param string                    $context Presentation context.
	 * @param array<string, mixed>|null $entry Entry.
	 * @param string                    $resolution_context Resolution context; public by default.
	 * @param array<string, mixed>      $options Optional explicit presentation overrides.
	 * @return string
	 */
	public function render( array $form, string $context = 'web', ?array $entry = null, string $resolution_context = 'public', array $options = array() ): string {
		$event = ( new EventService() )->event( $form, $entry, $resolution_context );
		if ( null === $event ) {
			return '';
		}
		$definitions = $this->definitions( $event, $form, $entry, $context, $resolution_context, $options );
		if ( empty( $definitions ) ) {
			return '';
		}
		$urls   = array();
		$labels = array();
		foreach ( $definitions as $provider => $definition ) {
			$urls[ $provider ]   = $definition['url'];
			$labels[ $provider ] = $definition['label'];
		}
		if ( 'text' === $context ) {
			$lines = array();
			foreach ( $urls as $provider => $url ) {
				$lines[] = $labels[ $provider ] . ': ' . esc_url_raw( $url );
			}
			return wp_strip_all_tags( apply_filters( 'stdgf_output_text', implode( "\n", $lines ), $event, $form, $entry, $context ) );
		}
		if ( 'email' === $context ) {
			$html = '<table role="presentation"><tr><td><p>' . esc_html__( 'Add this event to your calendar', 'savethedate-for-gravity-forms' ) . '</p>';
			foreach ( $urls as $provider => $url ) {
				$html .= '<p><a href="' . esc_url( $url ) . '">' . esc_html( $labels[ $provider ] ) . '</a></p>';
			}
			return wp_kses( apply_filters( 'stdgf_output_html', $html . '</td></tr></table>', $event, $form, $entry, $context ), $this->allowed_html() );
		}
		$settings     = \SaveTheDate\AddOn::get_instance()->get_plugin_settings();
		$style        = isset( $settings['style'] ) && 'outline' === $settings['style'] ? 'outline' : 'solid';
		$presentation = apply_filters(
			'stdgf_presentation',
			array(
				'style'   => $style,
				'layout'  => 'horizontal',
				'heading' => __( 'Add this event to your calendar', 'savethedate-for-gravity-forms' ),
			),
			$form,
			$entry,
			$context,
			$options
		);
		$style        = isset( $presentation['style'] ) && is_string( $presentation['style'] ) ? sanitize_html_class( $presentation['style'] ) : $style;
		$layout       = isset( $presentation['layout'] ) && is_string( $presentation['layout'] ) ? sanitize_html_class( $presentation['layout'] ) : 'horizontal';
		$heading      = isset( $presentation['heading'] ) && is_string( $presentation['heading'] ) ? sanitize_text_field( $presentation['heading'] ) : __( 'Add this event to your calendar', 'savethedate-for-gravity-forms' );
		$accent       = sanitize_hex_color( $settings['accent'] ?? '#2455a4' );
		$accent       = $accent ? $accent : '#2455a4';
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
		$template = apply_filters( 'stdgf_template_path', $template, $form, $entry, $context, $options );
		ob_start();
		do_action( 'stdgf_before_buttons', $event, $form );
		include $template;
		do_action( 'stdgf_after_buttons', $event, $form );
		$html = (string) ob_get_clean();
		return wp_kses( apply_filters( 'stdgf_output_html', apply_filters( 'stdgf_buttons_html', $html, $event, $form, $context ), $event, $form, $entry, $context ), $this->allowed_html() );
	}

	/**
	 * Get selected labeled URLs through the same resolver and hooks as rendering.
	 *
	 * @param array<string, mixed>      $form Form.
	 * @param string                    $context Presentation context.
	 * @param array<string, mixed>|null $entry Entry.
	 * @param string                    $resolution_context Resolution context.
	 * @param array<string, mixed>      $options Explicit presentation overrides.
	 * @return array<string, array<string, string>>
	 */
	public function providers( array $form, string $context = 'web', ?array $entry = null, string $resolution_context = 'public', array $options = array() ): array {
		$event = ( new EventService() )->event( $form, $entry, $resolution_context );
		return null === $event ? array() : $this->definitions( $event, $form, $entry, $context, $resolution_context, $options );
	}

	/**
	 * Build provider definitions without a second resolution or ambient state.
	 *
	 * @param Event                     $event Event.
	 * @param array<string, mixed>      $form Form.
	 * @param array<string, mixed>|null $entry Entry.
	 * @param string                    $context Presentation context.
	 * @param string                    $resolution_context Resolution context.
	 * @param array<string, mixed>      $options Presentation overrides.
	 * @return array<string, array<string, string>>
	 */
	private function definitions( Event $event, array $form, ?array $entry, string $context, string $resolution_context, array $options ): array {
		$fixed    = 'fixed' === ( $form['savethedate']['event_source'] ?? 'fixed' );
		$download = apply_filters( 'stdgf_download_url', $fixed ? rest_url( 'savethedate/v1/ics/form/' . (int) $form['id'] ) : '', $event, $form, $entry, $resolution_context );
		$urls     = ( new LinkBuilder() )->build( $event, is_string( $download ) ? $download : '' );
		if ( empty( $urls['ics'] ) ) {
			unset( $urls['ics'] );
		}
		$labels      = array(
			'google'    => __( 'Google Calendar', 'savethedate-for-gravity-forms' ),
			'outlook'   => __( 'Outlook.com', 'savethedate-for-gravity-forms' ),
			'office365' => __( 'Microsoft 365', 'savethedate-for-gravity-forms' ),
			'yahoo'     => __( 'Yahoo Calendar', 'savethedate-for-gravity-forms' ),
			'ics'       => __( 'Apple / Outlook (.ics)', 'savethedate-for-gravity-forms' ),
		);
		$definitions = array();
		foreach ( $urls as $provider => $url ) {
			$definitions[ $provider ] = array(
				'label' => $labels[ $provider ],
				'url'   => $url,
			);
		}
		$definitions = apply_filters( 'stdgf_provider_definitions', $definitions, $event, $form, $entry, $context, $options );
		$selection   = apply_filters( 'stdgf_provider_selection', array_keys( is_array( $definitions ) ? $definitions : array() ), $event, $form, $entry, $context, $options );
		$urls        = array();
		$labels      = array();
		foreach ( is_array( $selection ) ? $selection : array() as $provider ) {
			if ( ! is_string( $provider ) || ! isset( $definitions[ $provider ]['label'], $definitions[ $provider ]['url'] ) || ! is_string( $definitions[ $provider ]['label'] ) || ! is_string( $definitions[ $provider ]['url'] ) ) {
				continue;
			}
			$urls[ $provider ]   = $definitions[ $provider ]['url'];
			$labels[ $provider ] = $definitions[ $provider ]['label'];
		}
		foreach ( $urls as $provider => $url ) {
			$urls[ $provider ] = apply_filters( 'stdgf_link_url', $url, $provider, $event );
		}
		$result = array();
		foreach ( $urls as $provider => $url ) {
			if ( is_string( $url ) && '' !== $url ) {
				$result[ $provider ] = array(
					'url'   => $url,
					'label' => $labels[ $provider ],
				);
			}
		}
		return $result;
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
		return apply_filters(
			'stdgf_allowed_html',
			array(
				'table' => array( 'role' => true ),
				'tr'    => array(),
				'td'    => array(),
				'div'   => array(
					'class'      => true,
					'role'       => true,
					'aria-label' => true,
					'style'      => true,
				),
				'p'     => array( 'class' => true ),
				'span'  => array( 'class' => true ),
				'a'     => array(
					'class'    => true,
					'href'     => true,
					'target'   => true,
					'rel'      => true,
					'download' => true,
				),
			)
		);
	}
}
