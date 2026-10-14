<?php
/**
 * Calendar URLs.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Calendar;

/** Pure provider URL builder. */
final class LinkBuilder {
	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @param Event  $event Event.
	 * @param string $ics_url Ics url.
	 * @return array<string, string>
	 */
	public function build( Event $event, string $ics_url ): array {
		$details = $event->description . ( '' !== $event->url ? "\n\n" . $event->url : '' );
		$google  = array(
			'action'   => 'TEMPLATE',
			'text'     => $event->title,
			'dates'    => $event->date( $event->start ) . '/' . $event->date( $event->end ),
			'details'  => $details,
			'location' => $event->location,
		);
		$outlook = array(
			'rru'      => 'addevent',
			'subject'  => $event->title,
			'startdt'  => $event->date( $event->start, true ),
			'enddt'    => $event->date( $event->end, true ),
			'body'     => $details,
			'location' => $event->location,
		);
		$yahoo   = array(
			'v'      => '60',
			'title'  => $event->title,
			'st'     => $event->date( $event->start ),
			'et'     => $event->date( $event->end ),
			'desc'   => $details,
			'in_loc' => $event->location,
		);
		if ( $event->all_day ) {
			$outlook['allday'] = 'true';
			$yahoo['dur']      = 'allday';
		}
		return array(
			'google'    => 'https://calendar.google.com/calendar/render?' . $this->query( $google ),
			'outlook'   => 'https://outlook.live.com/calendar/0/action/compose?' . $this->query( $outlook ),
			'office365' => 'https://outlook.office.com/calendar/0/action/compose?' . $this->query( $outlook ),
			'yahoo'     => 'https://calendar.yahoo.com/?' . $this->query( $yahoo ),
			'ics'       => $ics_url,
		);
	}

	/**
	 * Process plugin data.
	 *
	 * @since 1.0.0
	 * @param array<string, string> $parameters Parameters.
	 * @return string
	 */
	private function query( array $parameters ): string {
		return http_build_query( $parameters, '', '&', PHP_QUERY_RFC3986 );
	}
}
