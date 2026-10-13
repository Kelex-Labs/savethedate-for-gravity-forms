<?php
/**
 * RFC 5545 calendar serialization.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Calendar;

defined( 'ABSPATH' ) || exit;

/** Pure iCalendar serializer. */
final class IcsBuilder {
	/**
	 * Serialize one event as raw CRLF-delimited calendar content.
	 *
	 * @since 1.0.0
	 * @param Event              $event Event.
	 * @param \DateTimeImmutable $now Now.
	 * @return string
	 */
	public function build( Event $event, \DateTimeImmutable $now ): string {
		$lines = array( 'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Kelex Labs//SaveTheDate for Gravity Forms//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'BEGIN:VEVENT', 'UID:' . $this->escape( $event->uid ), 'DTSTAMP:' . $now->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Ymd\THis\Z' ), ( $event->all_day ? 'DTSTART;VALUE=DATE:' : 'DTSTART:' ) . $event->date( $event->start ), ( $event->all_day ? 'DTEND;VALUE=DATE:' : 'DTEND:' ) . $event->date( $event->end ), 'SUMMARY:' . $this->escape( $event->title ), 'DESCRIPTION:' . $this->escape( $event->description ), 'LOCATION:' . $this->escape( $event->location ) );
		if ( '' !== $event->url ) {
			// URL is a URI, not a TEXT property; strip controls without TEXT escaping.
			$lines[] = 'URL:' . preg_replace( '/[\x00-\x20\x7f]/', '', $event->url );
		}
		$lines[] = 'END:VEVENT';
		$lines[] = 'END:VCALENDAR';
		return implode( "\r\n", array_map( array( $this, 'fold' ), $lines ) ) . "\r\n";
	}

	/**
	 * Escape TEXT values, preserving meaningful newlines.
	 *
	 * @since 1.0.0
	 * @param string $value Value.
	 * @return string
	 */
	public function escape( string $value ): string {
		$value = str_replace( array( "\r\n", "\r" ), "\n", $value );
		$value = (string) preg_replace( '/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', $value );
		return str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\\;', '\\,', '\\n' ), $value );
	}

	/**
	 * Fold at 75 octets including continuation space, preserving UTF-8.
	 *
	 * @since 1.0.0
	 * @param string $line Line.
	 * @return string
	 */
	public function fold( string $line ): string {
		$result = '';
		$limit  = 75;
		$length = strlen( $line );
		while ( $length > $limit ) {
			$cut = $limit;
			while ( $cut > 0 && ( ord( $line[ $cut ] ) & 0xc0 ) === 0x80 ) {
				--$cut;
			}
			$result .= substr( $line, 0, $cut ) . "\r\n ";
			$line    = substr( $line, $cut );
			$limit   = 74;
			$length  = strlen( $line );
		}
		return $result . $line;
	}
}
