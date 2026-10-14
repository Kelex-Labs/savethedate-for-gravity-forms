<?php
/**
 * Event value object.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Calendar;

/** Validated calendar data, independent of WordPress APIs. */
final class Event {
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $title;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var \DateTimeImmutable
	 */
	public $start;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var \DateTimeImmutable Exclusive end.
	 */
	public $end;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $all_day;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $location;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $description;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $url;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $uid;

	/**
	 * Optional organizer name and email.
	 *
	 * @var array<string, string>
	 */
	public $organizer = array();
	/**
	 * DISPLAY alarms, minutes before the start.
	 *
	 * @var array<int, int>
	 */
	public $reminders = array();
	/**
	 * Calendar revision; zero omits the optional SEQUENCE property.
	 *
	 * @var int
	 */
	public $sequence = 0;

	/**
	 * Return a copy with validated neutral calendar metadata.
	 *
	 * @param array<string, mixed> $metadata Metadata; omitted keys retain existing values.
	 * @return self
	 * @throws \InvalidArgumentException When metadata is invalid.
	 */
	public function with_metadata( array $metadata ): self {
		$copy = clone $this;
		if ( array_key_exists( 'organizer', $metadata ) ) {
			$organizer = $metadata['organizer'];
			if ( ! is_array( $organizer ) || ( ! empty( $organizer ) && ( ! isset( $organizer['email'] ) || ! is_string( $organizer['email'] ) || ! filter_var( $organizer['email'], FILTER_VALIDATE_EMAIL ) || preg_match( '/[\x00-\x20\x7f]/', $organizer['email'] ) || ( isset( $organizer['name'] ) && ! is_string( $organizer['name'] ) ) ) ) ) {
				throw new \InvalidArgumentException( 'invalid_organizer' );
			}
			$copy->organizer = empty( $organizer ) ? array() : array(
				'email' => $organizer['email'],
				'name'  => $organizer['name'] ?? '',
			);
		}
		if ( array_key_exists( 'reminders', $metadata ) ) {
			if ( ! is_array( $metadata['reminders'] ) || count( $metadata['reminders'] ) > 10 ) {
				throw new \InvalidArgumentException( 'invalid_reminders' );
			}
			foreach ( $metadata['reminders'] as $minutes ) {
				if ( ! is_int( $minutes ) || $minutes < 0 || $minutes > 40320 ) {
					throw new \InvalidArgumentException( 'invalid_reminder' );
				}
			}
			$copy->reminders = array_values( array_unique( $metadata['reminders'] ) );
		}
		if ( array_key_exists( 'sequence', $metadata ) ) {
			if ( ! is_int( $metadata['sequence'] ) || $metadata['sequence'] < 0 || $metadata['sequence'] > 2147483647 ) {
				throw new \InvalidArgumentException( 'invalid_sequence' );
			}
			$copy->sequence = $metadata['sequence'];
		}
		return $copy;
	}

	/**
	 * Construct a validated event.
	 *
	 * @since 1.0.0
	 * @param string               $title Title.
	 * @param \DateTimeImmutable   $start Start.
	 * @param \DateTimeImmutable   $end End.
	 * @param bool                 $all_day All day.
	 * @param string               $location Location.
	 * @param string               $description Description.
	 * @param string               $url Url.
	 * @param string               $uid Uid.
	 * @param array<string, mixed> $metadata Optional calendar metadata.
	 * @return void
	 * @throws \InvalidArgumentException When event input is invalid.
	 */
	public function __construct( string $title, \DateTimeImmutable $start, \DateTimeImmutable $end, bool $all_day, string $location, string $description, string $url, string $uid, array $metadata = array() ) {
		if ( $end <= $start || '' === trim( $title ) ) {
			throw new \InvalidArgumentException( 'invalid_event' );
		}
		$this->title       = $title;
		$this->start       = $start;
		$this->end         = $end;
		$this->all_day     = $all_day;
		$this->location    = $location;
		$this->description = $description;
		$this->url         = $url;
		$this->uid         = $uid;
		$validated         = $this->with_metadata( $metadata );
		$this->organizer   = $validated->organizer;
		$this->reminders   = $validated->reminders;
		$this->sequence    = $validated->sequence;
	}

	/**
	 * Whether the exclusive event end has been reached.
	 *
	 * @since 1.0.0
	 * @param \DateTimeImmutable $now Now.
	 * @return bool
	 */
	public function ended( \DateTimeImmutable $now ): bool {
		return $this->end <= $now;
	}

	/**
	 * Get the provider date or UTC date-time.
	 *
	 * @since 1.0.0
	 * @param \DateTimeImmutable $value Value.
	 * @param bool               $iso Iso.
	 * @return string
	 */
	public function date( \DateTimeImmutable $value, bool $iso = false ): string {
		if ( $this->all_day ) {
			return $value->format( $iso ? 'Y-m-d' : 'Ymd' );
		}
		return $value->setTimezone( new \DateTimeZone( 'UTC' ) )->format( $iso ? 'Y-m-d\TH:i:s\Z' : 'Ymd\THis\Z' );
	}
}
