<?php
/**
 * Default fixed event resolution.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Resolution;

use SaveTheDate\Calendar\Event;
use SaveTheDate\Calendar\EventFactory;

defined( 'ABSPATH' ) || exit;

/** Free supports only fixed events, including forms saved before source selection. */
final class FixedEventResolver implements EventResolver {
	/**
	 * Resolve a fixed event; unknown sources fail closed.
	 *
	 * @param array<string, mixed>      $settings Settings.
	 * @param array<string, mixed>      $form Form.
	 * @param array<string, mixed>|null $entry Entry.
	 * @param string                    $context Context.
	 * @return Event|null
	 */
	public function resolve( array $settings, array $form, ?array $entry, string $context ): ?Event {
		if ( 'fixed' !== ( $settings['event_source'] ?? 'fixed' ) ) {
			return null;
		}
		return ( new EventFactory() )->create( $settings, (int) $form['id'], sanitize_text_field( $form['title'] ?? '' ), wp_timezone_string(), home_url( '/' ) );
	}
}
