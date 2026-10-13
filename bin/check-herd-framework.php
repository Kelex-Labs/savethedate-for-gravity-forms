<?php
/** Offline checks against the installed Herd framework. No DB boot or changes. */
$stdgf_root = rtrim( $argv[1] ?? '', '/' );
if ( ! is_file( $stdgf_root . '/wp-includes/plugin.php' ) || ! is_file( $stdgf_root . '/wp-content/plugins/gravityforms/includes/addon/class-gf-addon.php' ) ) {
	fwrite( STDERR, "Usage: php bin/check-herd-framework.php /path/to/herd/site\n" );
	exit( 1 );
}
define( 'ABSPATH', $stdgf_root . '/' );
define( 'STDGF_VERSION', '1.0.0' );
define( 'STDGF_FILE', dirname( __DIR__ ) . '/savethedate-for-gravity-forms.php' );
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/compat.php';
require ABSPATH . 'wp-includes/utf8.php';
require ABSPATH . 'wp-includes/formatting.php';
require ABSPATH . 'wp-includes/kses.php';
spl_autoload_register( function ( $class ) {
	if ( 0 === strpos( $class, 'WP_HTML_' ) ) {
		$file = ABSPATH . 'wp-includes/html-api/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		if ( is_file( $file ) ) { require $file; }
	}
} );
require dirname( __DIR__ ) . '/vendor/autoload.php';

// Only database-dependent site values and translations are simulated.
function get_option( $name, $default = false ) { return 'blog_charset' === $name ? 'UTF-8' : $default; }
function is_utf8_charset( $charset = null ) { return _is_utf8_charset( $charset ?? 'UTF-8' ); }
function wp_allowed_protocols() { return array( 'http', 'https', 'mailto' ); }
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function wp_timezone_string() { return 'Europe/Malta'; }
function home_url( $path = '' ) { return 'https://events.test' . $path; }
function rest_url( $path = '' ) { return 'https://events.test/wp-json/' . $path; }
function locate_template( $path ) { return ''; }
function plugins_url( $path, $file ) { return 'https://events.test/' . $path; }
function wp_enqueue_style( ...$args ) {}
function rgar( $array, $key, $default = null ) { return isset( $array[ $key ] ) ? $array[ $key ] : $default; }
class GFForms { public static $version; }
class GFCommon { public static function get_base_path() { return ABSPATH . 'wp-content/plugins/gravityforms'; } }
$stdgf_source = file_get_contents( ABSPATH . 'wp-content/plugins/gravityforms/gravityforms.php' );
preg_match( '/public static \$version\s*=\s*\x27([^\x27]+)\x27/', $stdgf_source, $stdgf_match );
GFForms::$version = $stdgf_match[1];
require ABSPATH . 'wp-content/plugins/gravityforms/includes/addon/class-gf-addon.php';
$stdgf_addon = SaveTheDate\AddOn::get_instance();
GFAddOn::register( SaveTheDate\AddOn::class );
$stdgf_checks = 0;
function stdgf_assert( $valid, $message ) {
	global $stdgf_checks;
	if ( ! $valid ) { throw new RuntimeException( $message ); }
	++$stdgf_checks;
}
stdgf_assert( $stdgf_addon instanceof GFAddOn, 'Real GFAddOn inheritance failed.' );
stdgf_assert( has_action( 'init', array( $stdgf_addon, 'init' ) ) === 15, 'Native GF bootstrap timing failed.' );
stdgf_assert( in_array( SaveTheDate\AddOn::class, GFAddOn::get_registered_addons( false ), true ), 'Native registration failed.' );
$stdgf_form = array( 'id' => 12, 'title' => 'Workshop', 'is_active' => true, 'is_trash' => false, 'savethedate' => array( 'enabled' => '1', 'start_date' => '2027-07-01', 'start_time' => '10:00', 'end_date' => '2027-07-01', 'end_time' => '11:30', 'timezone' => 'Europe/Malta', 'url' => 'javascript:alert(1)', 'title' => '<script>alert(1)</script>Workshop' ) );
add_filter( 'stdgf_now', function () { return new DateTimeImmutable( '2027-01-01T00:00:00Z' ); } );
$stdgf_fields = $stdgf_addon->form_settings_fields( $stdgf_form );
stdgf_assert( 2 === count( $stdgf_fields ), 'Native form settings sections failed.' );
stdgf_assert( 'date' === $stdgf_fields[1]['fields'][2]['input_type'], 'HTML5 date configuration failed.' );
$stdgf_html = ( new SaveTheDate\Output\Renderer() )->render( $stdgf_form );
stdgf_assert( 5 === substr_count( $stdgf_html, 'href=' ), 'Real KSES stripped provider links.' );
stdgf_assert( false === strpos( $stdgf_html, '<script>' ), 'Unsafe markup survived real escaping.' );
stdgf_assert( false === strpos( $stdgf_html, 'javascript:' ), 'Unsafe event URL survived real sanitization.' );
stdgf_assert( false !== strpos( $stdgf_html, 'download="event.ics"' ), 'Real KSES removed download.' );
stdgf_assert( false !== strpos( $stdgf_html, '--stdgf-accent:' ), 'Real KSES removed CSS custom properties.' );
stdgf_assert( 1 === substr_count( $stdgf_addon->confirmation( $stdgf_html, $stdgf_form, array(), true ), 'role="group"' ), 'Native confirmation duplicated output.' );
fwrite( STDOUT, "PASS: {$stdgf_checks} checks using Herd's real GFAddOn " . GFForms::$version . " and WordPress sanitization, escaping, hooks and KSES. Database-dependent values are simulated; live QA is still required.\n" );
