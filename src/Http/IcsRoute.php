<?php
/**
 * Raw public calendar REST endpoint.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Http;

use SaveTheDate\Calendar\IcsBuilder;
use SaveTheDate\EventService;
defined( 'ABSPATH' ) || exit;

/** Serves no entry data; all configured event details are public. */
final class IcsRoute {
	/**
	 * Register the route and its raw response handler.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'savethedate/v1',
			'/ics/form/(?P<form_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'response' ),
				// Fixed event data is deliberately public; no submission is required.
				'permission_callback' => '__return_true',
				'args'                => array(
					'form_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'valid_id' ),
					),
				),
			)
		);
		add_filter( 'rest_pre_serve_request', array( $this, 'serve' ), 10, 4 );
	}

	/**
	 * Reject invalid IDs before sanitization.
	 *
	 * @since 1.0.0
	 * @param mixed $value Value.
	 * @return bool
	 */
	public function valid_id( $value ): bool {
		return is_scalar( $value ) && 1 === preg_match( '/^[1-9][0-9]*$/D', (string) $value );
	}

	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function response( \WP_REST_Request $request ) {
		$form    = \GFAPI::get_form( (int) $request['form_id'] );
		$service = new EventService();
		$event   = is_array( $form ) ? $service->event( $form, null, 'rest' ) : null;
		if ( null === $event ) {
			return new \WP_Error( 'stdgf_not_found', __( 'Event not found.', 'savethedate-for-gravity-forms' ), array( 'status' => 404 ) );
		}
		$now     = $service->now();
		$seconds = max( 0, min( 300, (int) floor( (float) $event->end->format( 'U.u' ) - (float) $now->format( 'U.u' ) ) ) );
		if ( $event->ended( $now ) ) {
			return new \WP_Error( 'stdgf_not_found', __( 'Event not found.', 'savethedate-for-gravity-forms' ), array( 'status' => 404 ) );
		}
		$content = ( new IcsBuilder() )->build( $event, $now );
		$content = apply_filters( 'stdgf_ics_content', $content, $event );
		return new \WP_REST_Response(
			$content,
			200,
			array(
				'Content-Type'           => 'text/calendar; charset=utf-8',
				'Content-Disposition'    => 'attachment; filename="event.ics"',
				'Cache-Control'          => 'public, max-age=' . $seconds . ', must-revalidate',
				'X-Robots-Tag'           => 'noindex',
				'X-Content-Type-Options' => 'nosniff',
			)
		);
	}

	/**
	 * Output only successful responses on this exact route as raw calendar data.
	 *
	 * @since 1.0.0
	 * @param bool              $served Served.
	 * @param \WP_REST_Response $response Response.
	 * @param \WP_REST_Request  $request Request.
	 * @param \WP_REST_Server   $server Server.
	 * @return bool
	 */
	public function serve( $served, \WP_REST_Response $response, \WP_REST_Request $request, \WP_REST_Server $server ): bool {
		if ( $served || 200 !== $response->get_status() || ! preg_match( '#^/savethedate/v1/ics/form/[0-9]+$#D', $request->get_route() ) || 'GET' !== $request->get_method() ) {
			return (bool) $served;
		}
		foreach ( $response->get_headers() as $name => $value ) {
			$server->send_header( $name, $value );
		}
		echo $response->get_data(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw text/calendar body escaped per RFC 5545, not HTML.
		do_action( 'stdgf_ics_served', (int) $request['form_id'] );
		return true;
	}
}
