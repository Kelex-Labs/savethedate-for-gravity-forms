<?php
use PHPUnit\Framework\TestCase;
use SaveTheDate\AddOn;
use SaveTheDate\EventService;
use SaveTheDate\Http\IcsRoute;
use SaveTheDate\Output\Renderer;

final class IntegrationTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['stdgf_hooks'] = array(); $GLOBALS['stdgf_styles'] = array();
		GFAddOn::$global_settings = array(); GFAddOn::$posted_settings = array();
		GFAPI::$forms = array( 12 => $this->form() );
		add_filter( 'stdgf_now', function () { return new DateTimeImmutable( '2027-07-01T08:00:00Z' ); } );
	}
	private function form( array $settings = array() ): array {
		return array( 'id' => 12, 'title' => 'Workshop', 'is_active' => true, 'is_trash' => false, 'savethedate' => array_merge( array( 'enabled' => '1', 'start_date' => '2027-07-01', 'start_time' => '10:00', 'end_date' => '2027-07-01', 'end_time' => '11:30', 'timezone' => 'Europe/Malta', 'auto_append' => '1' ), $settings ) );
	}
	private function request( int $id = 12 ): WP_REST_Request {
		$request = new WP_REST_Request( 'GET', '/savethedate/v1/ics/form/' . $id ); $request->set_param( 'form_id', $id ); return $request;
	}
	public function testEligibilityAcrossEveryOutput(): void {
		$forms = array( $this->form( array( 'enabled' => '0' ) ), $this->form( array( 'end_time' => '09:00' ) ), $this->form( array( 'end_time' => '10:00' ) ), $this->form( array( 'start_date' => 'bad' ) ), array_merge( $this->form(), array( 'is_active' => false ) ), array_merge( $this->form(), array( 'is_trash' => true ) ) );
		foreach ( $forms as $form ) {
			GFAPI::$forms[12] = $form;
			self::assertNull( ( new EventService() )->event( $form ) );
			self::assertSame( '', ( new Renderer() )->render( $form ) );
			self::assertSame( '', AddOn::get_instance()->shortcode( array( 'form' => '12' ) ) );
			$response = ( new IcsRoute() )->response( $this->request() ); self::assertInstanceOf( WP_Error::class, $response ); self::assertSame( 404, $response->data['status'] );
		}
	}
	public function testRawCalendarAndCacheLifetime(): void {
		$route = new IcsRoute(); $request = $this->request(); $response = $route->response( $request );
		self::assertSame( 'text/calendar; charset=utf-8', $response->get_headers()['Content-Type'] );
		self::assertSame( 'public, max-age=300, must-revalidate', $response->get_headers()['Cache-Control'] );
		$server = new WP_REST_Server(); ob_start(); $served = $route->serve( false, $response, $request, $server ); $body = ob_get_clean();
		self::assertTrue( $served ); self::assertSame( $response->get_data(), $body ); self::assertStringStartsWith( "BEGIN:VCALENDAR\r\n", $body );
		$GLOBALS['stdgf_hooks']['stdgf_now'] = array(); add_filter( 'stdgf_now', function () { return new DateTimeImmutable( '2027-07-01T09:29:40Z' ); } );
		self::assertSame( 'public, max-age=20, must-revalidate', $route->response( $request )->get_headers()['Cache-Control'] );
		$GLOBALS['stdgf_hooks']['stdgf_now'] = array(); add_filter( 'stdgf_now', function () { return new DateTimeImmutable( '2027-07-01T09:30:00Z' ); } );
		self::assertInstanceOf( WP_Error::class, $route->response( $request ) );
	}
	public function testRawServingOnlyMatchesSuccessfulGet(): void {
		$route = new IcsRoute();
		foreach ( array( array( '/other/route', 'GET', 200 ), array( '/savethedate/v1/ics/form/12', 'GET', 404 ), array( '/savethedate/v1/ics/form/12', 'POST', 200 ) ) as $case ) {
			ob_start(); $served = $route->serve( false, new WP_REST_Response( 'data', $case[2] ), new WP_REST_Request( $case[1], $case[0] ), new WP_REST_Server() ); $body = ob_get_clean(); self::assertFalse( $served ); self::assertSame( '', $body );
		}
		self::assertFalse( $route->valid_id( '-1' ) ); self::assertFalse( $route->valid_id( '0' ) ); self::assertTrue( $route->valid_id( '12' ) );
		self::assertInstanceOf( WP_Error::class, $route->response( $this->request( 99 ) ) );
	}
	public function testConfirmationAjaxAndNonAjaxAppendOnce(): void {
		$addon = AddOn::get_instance(); $form = $this->form();
		foreach ( array( true, false ) as $ajax ) {
			$html = $addon->confirmation( 'Thanks!', $form, array(), $ajax ); self::assertSame( 1, substr_count( $html, 'role="group"' ) );
			self::assertSame( $html, $addon->confirmation( $html, $form, array(), $ajax ) );
			$merged = $addon->merge_tag( 'Thanks! {save_the_date}', $form, array(), false, false, false, 'html' ); self::assertSame( $merged, $addon->confirmation( $merged, $form, array(), $ajax ) );
		}
		$redirect = array( 'redirect' => 'https://events.test' ); self::assertSame( $redirect, $addon->confirmation( $redirect, $form, array(), false ) );
		self::assertSame( 'Thanks!', $addon->confirmation( 'Thanks!', $this->form( array( 'auto_append' => '0' ) ), array(), false ) );
	}
	public function testMultipartEmailAndLiteralEventContent(): void {
		$addon = AddOn::get_instance(); $form = $this->form( array( 'description' => 'For {Name:1}' ) );
		$notification = $addon->notification( array( 'message' => 'Hello {save_the_date}', 'message_format' => 'multipart' ), $form, array() );
		$html = $addon->merge_tag( $notification['message'], $form, array(), false, false, false, 'html' );
		$text = $addon->merge_tag( $notification['message'], $form, array(), false, false, false, 'text' );
		self::assertStringContainsString( '<table role="presentation">', $html ); self::assertStringNotContainsString( 'stdgf-buttons', $html );
		self::assertStringNotContainsString( '<', $text ); self::assertStringContainsString( 'Google Calendar: https://', $text );
		self::assertStringContainsString( '%7BName%3A1%7D', $html );
	}
	public function testUnknownFormAndGlobalSettingsPreserved(): void {
		$addon = AddOn::get_instance(); GFAPI::$forms[12]['savethedate']['extension_key'] = array( 'value' => 42 );
		$addon->save_form_settings( $this->form(), array( 'title' => '<b>New title</b>', 'enabled' => '0' ) );
		self::assertSame( array( 'value' => 42 ), GFAPI::$forms[12]['savethedate']['extension_key'] ); self::assertSame( 'New title', GFAPI::$forms[12]['savethedate']['title'] );
		self::assertSame( '2027-07-01', GFAPI::$forms[12]['savethedate']['start_date'] );
		self::assertSame( '2027-07-01', GFAPI::$forms[12]['savethedate']['start_date'] );
		GFAddOn::$global_settings = array( 'extension_global' => 7 ); $addon->update_plugin_settings( array( 'style' => 'unknown', 'accent' => '#abc', 'delete_data' => '1' ) );
		self::assertSame( 7, GFAddOn::$global_settings['extension_global'] ); self::assertSame( 'solid', GFAddOn::$global_settings['style'] );
	}
	public function testShortcodeStylesEscapingAndAssetLoading(): void {
		GFAPI::$forms[12]['savethedate']['title'] = '<script>alert(1)</script>Title';
		$html = AddOn::get_instance()->shortcode( array( 'form' => 12, 'style' => 'unsupported' ) );
		self::assertStringNotContainsString( '<script>', $html ); self::assertStringContainsString( 'stdgf-buttons--solid', $html ); self::assertArrayHasKey( 'stdgf-frontend', $GLOBALS['stdgf_styles'] );
		GFAddOn::$global_settings = array( 'style' => 'outline', 'accent' => 'javascript:alert(1)' ); $html = AddOn::get_instance()->shortcode( array( 'form' => 12 ) );
		self::assertStringContainsString( 'stdgf-buttons--outline', $html ); self::assertStringContainsString( '--stdgf-accent:#2455a4', $html ); self::assertSame( '', AddOn::get_instance()->shortcode( array( 'form' => 999 ) ) );
	}
	public function testSettingsValidation(): void {
		$field = new class { public $error = ''; public function set_error( $error ) { $this->error = $error; } };
		GFAddOn::$posted_settings = $this->form( array( 'end_time' => '09:00' ) )['savethedate']; AddOn::get_instance()->validate_event( $field, '' ); self::assertNotSame( '', $field->error );
		$field->error = ''; GFAddOn::$posted_settings = $this->form()['savethedate']; AddOn::get_instance()->validate_event( $field, '' ); self::assertSame( '', $field->error );
	}
}
