<?php
use PHPUnit\Framework\TestCase;
use SaveTheDate\Calendar\EventFactory;
use SaveTheDate\Calendar\IcsBuilder;
use SaveTheDate\Calendar\LinkBuilder;

final class CalendarTest extends TestCase {
	private function settings( array $changes = array() ): array {
		return array_merge( array( 'title' => 'Workshop & café', 'start_date' => '2027-07-01', 'start_time' => '10:00', 'end_date' => '2027-07-01', 'end_time' => '11:30', 'timezone' => 'Europe/Malta', 'all_day' => false, 'location' => 'Hall, Malta', 'description' => "Hello; everyone\nJoin us", 'url' => 'https://events.test/join?a=1&b=2' ), $changes );
	}
	public function testTimezoneAndDstConversions(): void {
		$cases = array( array( 'Europe/Malta', '2027-07-01', '080000' ), array( 'Europe/Malta', '2027-01-01', '090000' ), array( 'America/New_York', '2027-07-01', '140000' ), array( 'America/New_York', '2027-01-01', '150000' ), array( 'Australia/Sydney', '2027-01-01', '230000' ), array( 'Australia/Sydney', '2027-07-01', '000000' ), array( 'Asia/Kolkata', '2027-07-01', '043000' ) );
		foreach ( $cases as $case ) {
			$event = ( new EventFactory() )->create( $this->settings( array( 'timezone' => $case[0], 'start_date' => $case[1], 'end_date' => $case[1] ) ), 12, 'Form', 'UTC', 'site' );
			self::assertStringEndsWith( 'T' . $case[2] . 'Z', $event->date( $event->start ) );
		}
	}
	public function testCrossingDstUsesActualElapsedTime(): void {
		$event = ( new EventFactory() )->create( $this->settings( array( 'timezone' => 'America/New_York', 'start_date' => '2027-03-14', 'end_date' => '2027-03-14', 'start_time' => '01:30', 'end_time' => '03:30' ) ), 1, 'Form', 'UTC', 'site' );
		self::assertSame( 3600, $event->end->getTimestamp() - $event->start->getTimestamp() );
	}
	public function testInvalidDatesAndRangesAreRejected(): void {
		foreach ( array( array( 'start_date' => '' ), array( 'start_date' => '2027-02-30' ), array( 'end_time' => '09:00' ), array( 'end_time' => '10:00' ), array( 'start_time' => '25:00' ), array( 'start_date' => '2027-03-14', 'end_date' => '2027-03-14', 'start_time' => '02:30', 'timezone' => 'America/New_York' ), array( 'all_day' => true, 'end_date' => '2027-06-30' ) ) as $changes ) {
			try { ( new EventFactory() )->create( $this->settings( $changes ), 1, 'Form', 'UTC', 'site' ); self::fail( 'Accepted invalid input.' ); }
			catch ( InvalidArgumentException $exception ) { self::assertContains( $exception->getMessage(), array( 'invalid_date', 'invalid_event' ) ); }
		}
	}
	public function testAllDayEndIsExclusiveAcrossProvidersAndIcs(): void {
		foreach ( array( '2027-07-01' => '20270702', '2027-07-03' => '20270704' ) as $inclusive => $exclusive ) {
			$event = ( new EventFactory() )->create( $this->settings( array( 'all_day' => true, 'end_date' => $inclusive ) ), 12, 'Form', 'UTC', 'site' );
			self::assertSame( $exclusive, $event->date( $event->end ) );
			$links = ( new LinkBuilder() )->build( $event, 'https://events.test/event.ics' );
			parse_str( parse_url( $links['google'], PHP_URL_QUERY ), $google );
			parse_str( parse_url( $links['outlook'], PHP_URL_QUERY ), $outlook );
			parse_str( parse_url( $links['yahoo'], PHP_URL_QUERY ), $yahoo );
			self::assertSame( '20270701/' . $exclusive, $google['dates'] );
			self::assertSame( 'true', $outlook['allday'] );
			self::assertSame( $event->end->format( 'Y-m-d' ), $outlook['enddt'] );
			self::assertSame( 'allday', $yahoo['dur'] ); self::assertSame( $exclusive, $yahoo['et'] );
			self::assertStringContainsString( 'DTEND;VALUE=DATE:' . $exclusive, ( new IcsBuilder() )->build( $event, new DateTimeImmutable( '2026-01-01' ) ) );
		}
	}
	public function testProviderUrlsHaveExactParameters(): void {
		$event = ( new EventFactory() )->create( $this->settings(), 12, 'Form', 'UTC', 'site' );
		$urls = ( new LinkBuilder() )->build( $event, 'https://events.test/event.ics' );
		self::assertSame( 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=Workshop%20%26%20caf%C3%A9&dates=20270701T080000Z%2F20270701T093000Z&details=Hello%3B%20everyone%0AJoin%20us%0A%0Ahttps%3A%2F%2Fevents.test%2Fjoin%3Fa%3D1%26b%3D2&location=Hall%2C%20Malta', $urls['google'] );
		self::assertSame( 'https://outlook.live.com/calendar/0/action/compose?rru=addevent&subject=Workshop%20%26%20caf%C3%A9&startdt=2027-07-01T08%3A00%3A00Z&enddt=2027-07-01T09%3A30%3A00Z&body=Hello%3B%20everyone%0AJoin%20us%0A%0Ahttps%3A%2F%2Fevents.test%2Fjoin%3Fa%3D1%26b%3D2&location=Hall%2C%20Malta', $urls['outlook'] );
		self::assertSame( str_replace( 'outlook.live.com', 'outlook.office.com', $urls['outlook'] ), $urls['office365'] );
		self::assertSame( 'https://calendar.yahoo.com/?v=60&title=Workshop%20%26%20caf%C3%A9&st=20270701T080000Z&et=20270701T093000Z&desc=Hello%3B%20everyone%0AJoin%20us%0A%0Ahttps%3A%2F%2Fevents.test%2Fjoin%3Fa%3D1%26b%3D2&in_loc=Hall%2C%20Malta', $urls['yahoo'] );
		self::assertSame( 'https://events.test/event.ics', $urls['ics'] );
	}
	public function testIcsEscapingAndUtf8Folding(): void {
		$builder = new IcsBuilder();
		self::assertSame( 'a\\,b\\;c\\\\d\\ne', $builder->escape( "a,b;c\\d\r\ne\x01" ) );
		$line = 'DESCRIPTION:' . str_repeat( 'ħü日本語🙂', 100 );
		$folded = $builder->fold( $line );
		self::assertSame( $line, str_replace( "\r\n ", '', $folded ) );
		foreach ( explode( "\r\n", $folded ) as $part ) { self::assertLessThanOrEqual( 75, strlen( $part ) ); self::assertSame( 1, preg_match( '//u', $part ) ); }
		$event = ( new EventFactory() )->create( $this->settings(), 12, 'Form', 'UTC', 'site' );
		$ics = $builder->build( $event, new DateTimeImmutable( '2026-01-01T00:00:00Z' ) );
		self::assertStringEndsWith( "END:VCALENDAR\r\n", $ics ); self::assertStringContainsString( "URL:https://events.test/join?a=1&b=2\r\n", $ics );
		self::assertStringNotContainsString( "\n", str_replace( "\r\n", '', $ics ) );
		self::assertTrue( $event->ended( $event->end ) ); self::assertFalse( $event->ended( $event->end->modify( '-1 second' ) ) );
	}
	public function testStableUidAndTimezoneFallback(): void {
		$factory = new EventFactory();
		$a = $factory->create( $this->settings( array( 'timezone' => 'invalid' ) ), 12, 'Form', 'Europe/Malta', 'site' );
		$b = $factory->create( $this->settings(), 12, 'Form', 'UTC', 'site' );
		self::assertSame( $a->uid, $b->uid );
		self::assertSame( 'UTC', $factory->timezone( 'invalid', 'also invalid' )->getName() );
		self::assertNotSame( $a->uid, $factory->create( $this->settings(), 13, 'Form', 'UTC', 'site' )->uid );
	}
}
