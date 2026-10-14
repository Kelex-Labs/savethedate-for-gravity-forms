<?php
/**
 * Shared public event lookup.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate;

use SaveTheDate\Calendar\Event;
use SaveTheDate\Resolution\EventResolver;
use SaveTheDate\Resolution\FixedEventResolver;
defined( 'ABSPATH' ) || exit;

/** The common eligibility gate for every public output. */
final class EventService {
	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>      $form Form.
	 * @param array<string, mixed>|null $entry Entry.
	 * @param string                    $context Context; public by default.
	 * @return Event|null
	 */
	public function event( array $form, ?array $entry = null, string $context = 'public' ): ?Event {
		$settings = isset( $form['savethedate'] ) && is_array( $form['savethedate'] ) ? $form['savethedate'] : array();
		if ( empty( $form['id'] ) || empty( $form['is_active'] ) || ! empty( $form['is_trash'] ) || empty( $settings['enabled'] ) ) {
			return null;
		}
		$source = $settings['event_source'] ?? 'fixed';
		// Public surfaces cannot be extended into entry-specific output.
		if ( 'fixed' !== $source && ( null === $entry || 'public' === $context || 'rest' === $context ) ) {
			return null;
		}
		$resolver = apply_filters( 'stdgf_event_resolver', new FixedEventResolver(), $source, $form, $entry, $context );
		if ( ! $resolver instanceof EventResolver ) {
			return null;
		}
		try {
			$event = $resolver->resolve( $this->sanitize( $settings ), $form, $entry, $context );
			if ( null !== $event ) {
				$metadata = apply_filters( 'stdgf_event_metadata', array(), $event, $form, $entry, $context );
				$event    = $event->with_metadata( is_array( $metadata ) ? $metadata : array() );
			}
		} catch ( \InvalidArgumentException $exception ) {
			return null;
		}
		return null === $event || $event->ended( $this->now() ) ? null : $event;
	}

	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $settings ): array {
		foreach ( array( 'title', 'location', 'timezone', 'start_date', 'start_time', 'end_date', 'end_time' ) as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$settings[ $key ] = sanitize_text_field( $settings[ $key ] );
			}
		}
		if ( array_key_exists( 'description', $settings ) ) {
			$settings['description'] = sanitize_textarea_field( $settings['description'] );
		}
		if ( array_key_exists( 'url', $settings ) ) {
			$settings['url'] = esc_url_raw( is_scalar( $settings['url'] ) ? (string) $settings['url'] : '', array( 'https', 'http' ) );
		}
		return $settings;
	}

	/**
	 * Injectable UTC clock for deterministic integration tests.
	 *
	 * @since 1.0.0
	 * @return \DateTimeImmutable
	 */
	public function now(): \DateTimeImmutable {
		return apply_filters( 'stdgf_now', new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) ) );
	}
}
