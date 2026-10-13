<?php
/**
 * Event value object.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Calendar;

defined( 'ABSPATH' ) || exit;

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
	 * Construct a validated event.
	 *
	 * @since 1.0.0
	 * @param string             $title Title.
	 * @param \DateTimeImmutable $start Start.
	 * @param \DateTimeImmutable $end End.
	 * @param bool               $all_day All day.
	 * @param string             $location Location.
	 * @param string             $description Description.
	 * @param string             $url Url.
	 * @param string             $uid Uid.
	 * @return void
	 * @throws \InvalidArgumentException When event input is invalid.
	 */
	public function __construct( string $title, \DateTimeImmutable $start, \DateTimeImmutable $end, bool $all_day, string $location, string $description, string $url, string $uid ) {
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
