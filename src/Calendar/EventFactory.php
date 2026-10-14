<?php
/**
 * Strict event parsing.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Calendar;

/** Converts sanitized settings into calendar data. */
final class EventFactory {
	/**
	 * Build an event; throws a stable error code for invalid input.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Settings.
	 * @param int                  $form_id Form id.
	 * @param string               $form_title Form title.
	 * @param string               $fallback_zone Fallback zone.
	 * @param string               $site_identity Site identity.
	 * @return Event
	 * @throws \InvalidArgumentException When event input is invalid.
	 */
	public function create( array $settings, int $form_id, string $form_title, string $fallback_zone, string $site_identity ): Event {
		$zone_name = (string) ( $settings['timezone'] ?? '' );
		$zone      = $this->timezone( $zone_name, $fallback_zone );
		$all_day   = ! empty( $settings['all_day'] );
		$start     = $this->parse( (string) ( $settings['start_date'] ?? '' ), (string) ( $settings['start_time'] ?? '' ), $all_day, $zone );
		$end       = $this->parse( (string) ( $settings['end_date'] ?? '' ), (string) ( $settings['end_time'] ?? '' ), $all_day, $zone );
		if ( $all_day ) {
			$end = $end->modify( '+1 day' );
		}
		$title = trim( (string) ( $settings['title'] ?? '' ) );
		$uid   = 'stdgf-' . hash( 'sha256', $site_identity . '|' . $form_id . '|' . $start->format( 'c' ) ) . '@savethedate';
		return new Event( '' === $title ? $form_title : $title, $start, $end, $all_day, (string) ( $settings['location'] ?? '' ), (string) ( $settings['description'] ?? '' ), (string) ( $settings['url'] ?? '' ), $uid );
	}

	/**
	 * Resolve invalid zones through the site zone, then UTC.
	 *
	 * @since 1.0.0
	 * @param string $requested Requested.
	 * @param string $fallback Fallback.
	 * @return \DateTimeZone
	 */
	public function timezone( string $requested, string $fallback ): \DateTimeZone {
		foreach ( array( $requested, $fallback, 'UTC' ) as $name ) {
			try {
				if ( '' !== $name ) {
					return new \DateTimeZone( $name );
				}
			} catch ( \Exception $exception ) {
				continue;
			}
		}
		return new \DateTimeZone( 'UTC' );
	}

	/**
	 * Parse exactly; reject overflow dates and nonexistent DST wall times.
	 *
	 * @since 1.0.0
	 * @param string        $date Date.
	 * @param string        $time Time.
	 * @param bool          $all_day All day.
	 * @param \DateTimeZone $zone Zone.
	 * @return \DateTimeImmutable
	 * @throws \InvalidArgumentException When event input is invalid.
	 */
	private function parse( string $date, string $time, bool $all_day, \DateTimeZone $zone ): \DateTimeImmutable {
		$format = $all_day ? 'Y-m-d' : 'Y-m-d H:i';
		$input  = $all_day ? $date : $date . ' ' . $time;
		$value  = \DateTimeImmutable::createFromFormat( '!' . $format, $input, $zone );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $value || ( false !== $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) || $value->format( $format ) !== $input ) {
			throw new \InvalidArgumentException( 'invalid_date' );
		}
		return $value;
	}
}
