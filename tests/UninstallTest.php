<?php
use PHPUnit\Framework\TestCase;

final class UninstallTest extends TestCase {
	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testDefaultPreservesFormsAndSettings(): void {
		define( 'WP_UNINSTALL_PLUGIN', 'savethedate-for-gravity-forms' );
		define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/stdgf-no-companion' );
		GFAPI::$forms = array( 1 => array( 'id' => 1, 'is_active' => true, 'is_trash' => false, 'savethedate' => array( 'enabled' => '1' ) ) );
		$GLOBALS['stdgf_options']['gravityformsaddon_savethedate_settings'] = array( 'delete_data' => '0' );
		require dirname( __DIR__ ) . '/uninstall.php';
		self::assertArrayHasKey( 'savethedate', GFAPI::$forms[1] );
		self::assertArrayHasKey( 'gravityformsaddon_savethedate_settings', $GLOBALS['stdgf_options'] );
	}
	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testOptInRemovesOnlyAddonDataFromActiveInactiveAndTrashedForms(): void {
		define( 'WP_UNINSTALL_PLUGIN', 'savethedate-for-gravity-forms' );
		define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/stdgf-no-companion' );
		GFAPI::$forms = array();
		foreach ( array( array( true, false ), array( false, false ), array( true, true ) ) as $key => $state ) {
			GFAPI::$forms[$key+1] = array( 'id' => $key+1, 'is_active' => $state[0], 'is_trash' => $state[1], 'savethedate' => array( 'enabled' => '1' ), 'other_addon' => array( 'keep' => true ) );
		}
		$GLOBALS['stdgf_options']['gravityformsaddon_savethedate_settings'] = array( 'delete_data' => '1' );
		require dirname( __DIR__ ) . '/uninstall.php';
		self::assertCount( 3, GFAPI::$forms );
		foreach ( GFAPI::$forms as $form ) { self::assertArrayNotHasKey( 'savethedate', $form ); self::assertTrue( $form['other_addon']['keep'] ); }
		self::assertArrayNotHasKey( 'gravityformsaddon_savethedate_settings', $GLOBALS['stdgf_options'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testMultisitePaginationRespectsEachSitesOptIn(): void {
		define( 'WP_UNINSTALL_PLUGIN', 'savethedate-for-gravity-forms' );
		define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/stdgf-no-companion' );
		$GLOBALS['stdgf_multisite'] = true;
		$GLOBALS['stdgf_blog_data'] = array();
		for ( $id = 1; $id <= 103; ++$id ) {
			$GLOBALS['stdgf_blog_data'][$id] = array( 'options' => array( 'gravityformsaddon_savethedate_settings' => array( 'delete_data' => 0 === $id % 2 ? '1' : '0' ) ), 'forms' => array( 1 => array( 'id' => 1, 'is_active' => true, 'is_trash' => false, 'savethedate' => array( 'enabled' => '1' ), 'other_addon' => true ) ) );
		}
		$GLOBALS['stdgf_blog_id'] = 1;
		GFAPI::$forms = $GLOBALS['stdgf_blog_data'][1]['forms'];
		$GLOBALS['stdgf_options'] = $GLOBALS['stdgf_blog_data'][1]['options'];
		require dirname( __DIR__ ) . '/uninstall.php';
		foreach ( $GLOBALS['stdgf_blog_data'] as $id => $site ) {
			self::assertSame( 1 === $id % 2, isset( $site['forms'][1]['savethedate'] ) );
			self::assertTrue( $site['forms'][1]['other_addon'] );
		}
		self::assertSame( array(), $GLOBALS['stdgf_blog_stack'] );
	}

	public function testInstalledCompanionPreservesSettings(): void {
		$output = array();
		$status = 0;
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/uninstall-fixture.php' ) . ' companion', $output, $status );
		self::assertSame( 0, $status );
		$data = json_decode( implode( "\n", $output ), true );
		self::assertArrayHasKey( 'savethedate', $data['forms'][12] );
		self::assertArrayHasKey( 'gravityformsaddon_savethedate_settings', $data['options'] );
	}
}
