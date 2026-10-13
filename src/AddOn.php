<?php
/**
 * Gravity Forms settings and output hooks.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate;

use SaveTheDate\Calendar\EventFactory;
use SaveTheDate\Http\IcsRoute;
use SaveTheDate\Output\Renderer;
defined( 'ABSPATH' ) || exit;

/** Native Gravity Forms add-on. */
class AddOn extends \GFAddOn {
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var self|null
	 */
	private static $instance;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_version = STDGF_VERSION;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_min_gravityforms_version = '2.7';
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_slug = 'savethedate';
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_path = 'savethedate-for-gravity-forms/savethedate-for-gravity-forms.php';
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_full_path = STDGF_FILE;
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_title = 'SaveTheDate for Gravity Forms';
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_short_title = 'SaveTheDate';
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_capabilities_form_settings = 'gravityforms_edit_forms';
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_capabilities_settings_page = 'gravityforms_edit_settings';
	/**
	 * Stored class data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $_capabilities_uninstall = 'gravityforms_uninstall';

	/**
	 * Return the Gravity Forms singleton.
	 *
	 * @since 1.0.0
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize after Gravity Forms and WordPress are ready.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		parent::init();
		add_filter( 'gform_confirmation', array( $this, 'confirmation' ), 20, 4 );
		add_filter( 'gform_replace_merge_tags', array( $this, 'merge_tag' ), 20, 7 );
		add_filter( 'gform_custom_merge_tags', array( $this, 'custom_merge_tags' ), 10, 4 );
		add_filter( 'gform_notification', array( $this, 'notification' ), 20, 3 );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue' ), 10, 2 );
		add_shortcode( 'savethedate', array( $this, 'shortcode' ) );
		$route = new IcsRoute();
		add_action( 'rest_api_init', array( $route, 'register' ) );
		do_action( 'stdgf_register_modules', $this );
	}

	/**
	 * Native form settings; GF handles nonce, capabilities and form duplication.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form Form.
	 * @return array<int, array<string, mixed>>
	 */
	public function form_settings_fields( $form ) {
		$zones = array();
		foreach ( \DateTimeZone::listIdentifiers() as $zone ) {
			$zones[] = array(
				'label' => $zone,
				'value' => $zone,
			);
		}
		$site_zone = wp_timezone_string();
		if ( ! in_array( $site_zone, \DateTimeZone::listIdentifiers(), true ) ) {
			$site_zone = 'UTC';
		}
		$fields   = array(
			array(
				'name'        => 'title',
				'label'       => esc_html__( 'Event title', 'savethedate-for-gravity-forms' ),
				'type'        => 'text',
				'placeholder' => $form['title'] ?? '',
			),
			array(
				'name'  => 'all_day',
				'label' => esc_html__( 'All-day event', 'savethedate-for-gravity-forms' ),
				'type'  => 'toggle',
			),
			array(
				'name'       => 'start_date',
				'label'      => esc_html__( 'Start date', 'savethedate-for-gravity-forms' ),
				'type'       => 'text',
				'input_type' => 'date',
			),
			array(
				'name'       => 'start_time',
				'label'      => esc_html__( 'Start time', 'savethedate-for-gravity-forms' ),
				'type'       => 'text',
				'input_type' => 'time',
				'dependency' => array(
					'field'  => 'all_day',
					'values' => array( '0', '' ),
				),
			),
			array(
				'name'                => 'end_date',
				'label'               => esc_html__( 'End date (inclusive for all-day events)', 'savethedate-for-gravity-forms' ),
				'type'                => 'text',
				'input_type'          => 'date',
				'validation_callback' => array( $this, 'validate_event' ),
			),
			array(
				'name'       => 'end_time',
				'label'      => esc_html__( 'End time', 'savethedate-for-gravity-forms' ),
				'type'       => 'text',
				'input_type' => 'time',
				'dependency' => array(
					'field'  => 'all_day',
					'values' => array( '0', '' ),
				),
			),
			array(
				'name'          => 'timezone',
				'label'         => esc_html__( 'Timezone', 'savethedate-for-gravity-forms' ),
				'type'          => 'select',
				'choices'       => $zones,
				'default_value' => $site_zone,
				'description'   => esc_html__( 'Choose the event timezone. Sites using a numeric UTC offset default to UTC here. Repeated autumn DST times use PHP timezone rules; choose an unambiguous time when possible.', 'savethedate-for-gravity-forms' ),
			),
			array(
				'name'  => 'location',
				'label' => esc_html__( 'Location', 'savethedate-for-gravity-forms' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'description',
				'label' => esc_html__( 'Description (plain text)', 'savethedate-for-gravity-forms' ),
				'type'  => 'textarea',
			),
			array(
				'name'       => 'url',
				'label'      => esc_html__( 'Event URL', 'savethedate-for-gravity-forms' ),
				'type'       => 'text',
				'input_type' => 'url',
			),
			array(
				'name'          => 'auto_append',
				'label'         => esc_html__( 'Add buttons to Text confirmations automatically', 'savethedate-for-gravity-forms' ),
				'type'          => 'toggle',
				'default_value' => '1',
			),
		);
		$sections = array(
			array(
				'title'       => esc_html__( 'Event', 'savethedate-for-gravity-forms' ),
				'description' => esc_html__( 'Event details and calendar downloads are public. Do not enter private information.', 'savethedate-for-gravity-forms' ),
				'fields'      => array(
					array(
						'name'  => 'enabled',
						'label' => esc_html__( 'This form is an event', 'savethedate-for-gravity-forms' ),
						'type'  => 'toggle',
					),
				),
			),
			array(
				'title'       => esc_html__( 'Event details', 'savethedate-for-gravity-forms' ),
				'description' => $this->event_notice( $form ),
				'dependency'  => array(
					'field'  => 'enabled',
					'values' => array( '1' ),
				),
				'fields'      => $fields,
			),
		);
		return apply_filters( 'stdgf_form_settings_sections', $sections, $form );
	}

	/**
	 * Validate the complete posted event using the Settings API's values.
	 *
	 * @since 1.0.0
	 * @param object $field Field.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public function validate_event( $field, $value ) {
		$settings = $this->get_posted_settings();
		if ( empty( $settings['enabled'] ) ) {
			return;
		}
		try {
			( new EventFactory() )->create( ( new EventService() )->sanitize( $settings ), 1, 'Event', wp_timezone_string(), home_url( '/' ) );
		} catch ( \InvalidArgumentException $exception ) {
			$field->set_error( esc_html__( 'Enter valid dates and times with the end after the start. An all-day end date includes that day. Times skipped by daylight saving changes are invalid.', 'savethedate-for-gravity-forms' ) );
		}
	}

	/**
	 * Warn only within this form's settings tab.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form Form.
	 * @return string
	 */
	private function event_notice( array $form ): string {
		$settings = $form['savethedate'] ?? array();
		if ( empty( $settings['enabled'] ) ) {
			return '';
		}
		$message = '';
		try {
			$factory = new EventFactory();
			$event   = $factory->create( ( new EventService() )->sanitize( $settings ), (int) $form['id'], $form['title'], wp_timezone_string(), home_url( '/' ) );
			if ( $event->ended( ( new EventService() )->now() ) ) {
				$message = esc_html__( 'This event has ended. Buttons are hidden. If you duplicated this form, update its dates.', 'savethedate-for-gravity-forms' );
			}
			$zone = (string) ( $settings['timezone'] ?? '' );
			if ( '' === $zone || $factory->timezone( $zone, wp_timezone_string() )->getName() !== $zone ) {
				$message .= ' ' . esc_html__( 'The configured timezone is invalid or empty. The site timezone, then UTC, is used as a fallback.', 'savethedate-for-gravity-forms' );
			}
		} catch ( \InvalidArgumentException $exception ) {
			$message = esc_html__( 'The event dates are invalid. Buttons and downloads are hidden until you correct them.', 'savethedate-for-gravity-forms' );
		}
		return '' === $message ? '' : '<div class="notice notice-warning inline"><p>' . $message . '</p></div>';
	}

	/**
	 * Sanitize known fields and preserve unknown metadata even on older GF versions.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form Form.
	 * @param array<string, mixed> $settings Settings.
	 * @return bool
	 */
	public function save_form_settings( $form, $settings ) {
		$existing = \GFAPI::get_form( (int) $form['id'] );
		$previous = is_array( $existing ) && isset( $existing['savethedate'] ) && is_array( $existing['savethedate'] ) ? $existing['savethedate'] : array();
		$settings = ( new EventService() )->sanitize( $settings );
		return parent::save_form_settings( $form, array_merge( $previous, $settings ) );
	}

	/**
	 * Global style and opt-in cleanup settings.
	 *
	 * @since 1.0.0
	 * @return array<int, array<string, mixed>>
	 */
	public function plugin_settings_fields() {
		return apply_filters(
			'stdgf_plugin_settings_fields',
			array(
				array(
					'title'  => esc_html__( 'SaveTheDate settings', 'savethedate-for-gravity-forms' ),
					'fields' => array(
						array(
							'name'          => 'style',
							'label'         => esc_html__( 'Button style', 'savethedate-for-gravity-forms' ),
							'type'          => 'select',
							'default_value' => 'solid',
							'choices'       => array(
								array(
									'label' => esc_html__( 'Solid', 'savethedate-for-gravity-forms' ),
									'value' => 'solid',
								),
								array(
									'label' => esc_html__( 'Outline', 'savethedate-for-gravity-forms' ),
									'value' => 'outline',
								),
							),
						),
						array(
							'name'                => 'accent',
							'label'               => esc_html__( 'Accent color', 'savethedate-for-gravity-forms' ),
							'type'                => 'text',
							'default_value'       => '#2455a4',
							'validation_callback' => array( $this, 'validate_color' ),
						),
						array(
							'name'          => 'delete_data',
							'label'         => esc_html__( 'Remove SaveTheDate settings on uninstall', 'savethedate-for-gravity-forms' ),
							'type'          => 'toggle',
							'default_value' => '0',
							'description'   => esc_html__( 'Off by default. Applies separately to each site in a multisite network. Forms and entries are always preserved.', 'savethedate-for-gravity-forms' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Validate colors before persisting.
	 *
	 * @since 1.0.0
	 * @param object $field Field.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public function validate_color( $field, $value ) {
		if ( ! sanitize_hex_color( $value ) ) {
			$field->set_error( esc_html__( 'Enter a valid hex color, such as #2455a4.', 'savethedate-for-gravity-forms' ) );
		}
	}

	/**
	 * Preserve unknown global keys and sanitize known values.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	public function update_plugin_settings( $settings ) {
		$old                     = $this->get_plugin_settings();
		$settings['accent']      = sanitize_hex_color( $settings['accent'] ?? '' );
		$settings['style']       = isset( $settings['style'] ) && 'outline' === $settings['style'] ? 'outline' : 'solid';
		$settings['delete_data'] = empty( $settings['delete_data'] ) ? '0' : '1';
		parent::update_plugin_settings( array_merge( is_array( $old ) ? $old : array(), $settings ) );
	}

	/**
	 * Append once to Text confirmations, including AJAX responses.
	 *
	 * @since 1.0.0
	 * @param string|array<string, mixed> $confirmation Confirmation.
	 * @param array<string, mixed>        $form Form.
	 * @param array<string, mixed>        $entry Entry.
	 * @param bool                        $ajax Ajax.
	 * @return string|array<string, mixed>
	 */
	public function confirmation( $confirmation, $form, $entry, $ajax ) {
		if ( ! is_string( $confirmation ) || false !== strpos( $confirmation, 'stdgf-buttons' ) || false !== strpos( $confirmation, '{save_the_date}' ) ) {
			return $confirmation;
		}
		$settings = $form['savethedate'] ?? array();
		if ( isset( $settings['auto_append'] ) && empty( $settings['auto_append'] ) ) {
			return $confirmation;
		}
		return $confirmation . ( new Renderer() )->render( $form );
	}

	/**
	 * Replace the merge tag without processing event content as merge tags.
	 *
	 * @since 1.0.0
	 * @param string               $text Text.
	 * @param array<string, mixed> $form Form.
	 * @param array<string, mixed> $entry Entry.
	 * @param bool                 $url_encode Url encode.
	 * @param bool                 $esc_html Esc html.
	 * @param bool                 $nl2br Nl2br.
	 * @param string               $format Format.
	 * @return string
	 */
	public function merge_tag( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		if ( ( false === strpos( $text, '{save_the_date}' ) && false === strpos( $text, '{stdgf_save_the_date_email}' ) ) || ! is_array( $form ) ) {
			return $text;
		}
		$context = 'text' === $format ? 'text' : ( false !== strpos( $text, '{stdgf_save_the_date_email}' ) ? 'email' : 'web' );
		$html    = ( new Renderer() )->render( $form, $context );
		return str_replace( array( '{save_the_date}', '{stdgf_save_the_date_email}' ), $url_encode ? rawurlencode( $html ) : $html, $text );
	}

	/**
	 * Register a merge tag for the Gravity Forms editor.
	 *
	 * @since 1.0.0
	 * @param array<int, array<string, string>> $tags Tags.
	 * @param int                               $form_id Form id.
	 * @param array<int, mixed>                 $fields Fields.
	 * @param string                            $element_id Element id.
	 * @return array<int, array<string, string>>
	 */
	public function custom_merge_tags( $tags, $form_id, $fields, $element_id ) {
		$tags[] = array(
			'label' => esc_html__( 'SaveTheDate calendar buttons', 'savethedate-for-gravity-forms' ),
			'tag'   => '{save_the_date}',
		);
		return $tags;
	}

	/**
	 * Resolve notification tags early with email-friendly or plain-text markup.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $notification Notification.
	 * @param array<string, mixed> $form Form.
	 * @param array<string, mixed> $entry Entry.
	 * @return array<string, mixed>
	 */
	public function notification( $notification, $form, $entry ) {
		if ( isset( $notification['message'] ) && false !== strpos( $notification['message'], '{save_the_date}' ) ) {
			// Defer rendering until GF requests each format, including multipart mail.
			$notification['message'] = str_replace( '{save_the_date}', '{stdgf_save_the_date_email}', $notification['message'] );
		}
		return $notification;
	}

	/**
	 * Preload CSS for event forms so AJAX responses have styles.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form Form.
	 * @param bool                 $ajax Ajax.
	 * @return void
	 */
	public function enqueue( $form, $ajax ) {
		if ( null !== ( new EventService() )->event( $form ) ) {
			( new Renderer() )->enqueue();
		}
	}

	/**
	 * Basic fixed-event shortcode; extra attributes are ignored.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $attributes Attributes.
	 * @return string
	 */
	public function shortcode( $attributes ): string {
		$attributes = shortcode_atts( array( 'form' => '0' ), $attributes, 'savethedate' );
		$form       = \GFAPI::get_form( absint( $attributes['form'] ) );
		return is_array( $form ) ? ( new Renderer() )->render( $form ) : '';
	}
}
