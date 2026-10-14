<?php
/** Deterministic WP/GF doubles for unit tests; not a live-site compatibility claim. */
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/stubs/gravityforms.php';
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
$GLOBALS['stdgf_hooks'] = array();
$GLOBALS['stdgf_styles'] = array();
$GLOBALS['stdgf_options'] = array();
function add_filter( $tag, $callback, $priority = 10, $args = 1 ) { $GLOBALS['stdgf_hooks'][$tag][$priority][] = array( $callback, $args ); }
function add_action( $tag, $callback, $priority = 10, $args = 1 ) { add_filter( $tag, $callback, $priority, $args ); }
function apply_filters( $tag, $value, ...$args ) { $hooks = $GLOBALS['stdgf_hooks'][$tag] ?? array(); ksort( $hooks ); foreach ( $hooks as $callbacks ) { foreach ( $callbacks as $item ) { $value = call_user_func_array( $item[0], array_slice( array_merge( array( $value ), $args ), 0, $item[1] ) ); } } return $value; }
function do_action( $tag, ...$args ) { $hooks = $GLOBALS['stdgf_hooks'][$tag] ?? array(); ksort( $hooks ); foreach ( $hooks as $callbacks ) { foreach ( $callbacks as $item ) { call_user_func_array( $item[0], array_slice( $args, 0, $item[1] ) ); } } }
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url_raw( $url, $protocols = array( 'http', 'https' ) ) { return preg_match( '#^https?://#', $url ) ? preg_replace( '/[\x00-\x20\x7f]/', '', $url ) : ''; }
function esc_url( $url ) { return esc_attr( esc_url_raw( $url ) ); }
function sanitize_text_field( $value ) { return is_scalar( $value ) ? trim( strip_tags( (string) $value ) ) : ''; }
function sanitize_textarea_field( $value ) { return sanitize_text_field( $value ); }
function sanitize_hex_color( $value ) { return preg_match( '/^#(?:[a-fA-F0-9]{3}){1,2}$/D', (string) $value ) ? $value : null; }
function home_url( $path = '' ) { return 'https://events.test' . $path; }
function rest_url( $path = '' ) { return 'https://events.test/wp-json/' . $path; }
function wp_timezone_string() { return 'Europe/Malta'; }
function wp_enqueue_style( $handle, $url, $deps, $version ) { $GLOBALS['stdgf_styles'][$handle] = $url; }
function plugins_url( $path, $file ) { return 'https://events.test/wp-content/plugins/savethedate-for-gravity-forms/' . $path; }
function locate_template( $path ) { return ''; }
function wp_kses( $html, $allowed ) { return strip_tags( $html, '<' . implode( '><', array_keys( $allowed ) ) . '>' ); }
function absint( $value ) { return abs( (int) $value ); }
function shortcode_atts( $defaults, $attributes, $tag ) { return array_intersect_key( (array) $attributes, $defaults ) + $defaults; }
function add_shortcode( $tag, $callback ) {}
function load_plugin_textdomain( ...$args ) {}
function plugin_basename( $path ) { return basename( dirname( $path ) ) . '/' . basename( $path ); }
function register_rest_route( ...$args ) {}
function get_option( $name, $default = false ) { return $GLOBALS['stdgf_options'][$name] ?? $default; }
function delete_option( $name ) { unset( $GLOBALS['stdgf_options'][$name] ); }
function is_multisite() { return ! empty( $GLOBALS['stdgf_multisite'] ); }
function get_sites( $args ) { return array_slice( array_keys( $GLOBALS['stdgf_blog_data'] ), $args['offset'], $args['number'] ); }
function switch_to_blog( $id ) {
	$previous = $GLOBALS['stdgf_blog_id'] ?? 1;
	$GLOBALS['stdgf_blog_stack'][] = $previous;
	$GLOBALS['stdgf_blog_data'][$previous] = array( 'forms' => GFAPI::$forms, 'options' => $GLOBALS['stdgf_options'] );
	$GLOBALS['stdgf_blog_id'] = $id;
	GFAPI::$forms = $GLOBALS['stdgf_blog_data'][$id]['forms'];
	$GLOBALS['stdgf_options'] = $GLOBALS['stdgf_blog_data'][$id]['options'];
}
function restore_current_blog() {
	$id = $GLOBALS['stdgf_blog_id'];
	$GLOBALS['stdgf_blog_data'][$id] = array( 'forms' => GFAPI::$forms, 'options' => $GLOBALS['stdgf_options'] );
	$previous = array_pop( $GLOBALS['stdgf_blog_stack'] );
	$GLOBALS['stdgf_blog_id'] = $previous;
	GFAPI::$forms = $GLOBALS['stdgf_blog_data'][$previous]['forms'];
	$GLOBALS['stdgf_options'] = $GLOBALS['stdgf_blog_data'][$previous]['options'];
}
class WP_REST_Request implements ArrayAccess {
	private $params = array();
	private $method;
	private $route;
	public function __construct( $method = 'GET', $route = '' ) { $this->method = $method; $this->route = $route; }
	public function set_param( $key, $value ) { $this->params[$key] = $value; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
	public function offsetExists( $offset ): bool { return isset( $this->params[$offset] ); }
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) { return $this->params[$offset] ?? null; }
	public function offsetSet( $offset, $value ): void { $this->params[$offset] = $value; }
	public function offsetUnset( $offset ): void { unset( $this->params[$offset] ); }
}
class WP_REST_Response {
	private $data;
	private $status;
	private $headers;
	public function __construct( $data, $status = 200, $headers = array() ) { $this->data = $data; $this->status = $status; $this->headers = $headers; }
	public function get_status() { return $this->status; }
	public function get_data() { return $this->data; }
	public function get_headers() { return $this->headers; }
}
class WP_REST_Server { public $headers = array(); public function send_header( $name, $value ) { $this->headers[$name] = $value; } }
class WP_Error { public $code; public $data; public function __construct( $code, $message, $data ) { $this->code = $code; $this->data = $data; } }

function wp_strip_all_tags( $text ) { return strip_tags( $text ); }

function sanitize_html_class($value){return preg_replace('/[^a-zA-Z0-9_-]/','',$value);}
