<?php
namespace Qazana;

use Qazana\Core\Settings\Manager as SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Typography control.
 *
 * A base control for creating typography control. Displays input fields to define
 * the content typography including font size, font family, font weight, text
 * transform, font style, line height and letter spacing.
 *
 * Creating new control in the editor (inside `Widget_Base::_register_controls()`
 * method):
 *
 *    $this->add_group_control(
 *    	Group_Control_Typography::get_type(),
 *    	[
 *    		'name' => 'content_typography',
 *    		'scheme' => Scheme_Typography::TYPOGRAPHY_1,
 *    		'selector' => '{{WRAPPER}} .text',
 *    		'separator' => 'before',
 *    	]
 *    );
 *
 * @since 1.0.0
 *
 * @param string $name        The field name.
 * @param string $separator   Optional. Set the position of the control separator.
 *                            Available values are 'default', 'before', 'after'
 *                            and 'none'. 'default' will position the separator
 *                            depending on the control type. 'before' / 'after'
 *                            will position the separator before/after the
 *                            control. 'none' will hide the separator. Default
 *                            is 'default'.
 */
class Group_Control_Typography extends Group_Control_Base {

	/**
	 * Fields.
	 *
	 * Holds all the typography control fields.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @var array Typography control fields.
	 */
	protected static $fields;

	/**
	 * Scheme fields keys.
	 *
	 * Holds all the typography control scheme fields keys.
	 * Default is an array containing `font_family` and `font_weight`.
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 *
	 * @var array Typography control scheme fields keys.
	 */
	private static $_scheme_fields_keys = [ 'font_family', 'font_weight' ];

	/**
	 * Retrieve scheme fields keys.
	 *
	 * Get all the available typography control scheme fields keys.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return string Scheme fields keys.
	 */
	public static function get_scheme_fields_keys() {
		return self::$_scheme_fields_keys;
	}

	/**
	 * Retrieve type.
	 *
	 * Get typography control type.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return string Control type.
	 */
	public static function get_type() {
		return 'typography';
	}

	/**
	 * Init fields.
	 *
	 * Initialize typography control fields.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array Control fields.
	 */
	protected function init_fields() {
		$fields = [];

		$default_fonts = SettingsManager::get_settings_managers( 'general' )->get_model()->get_settings( 'qazana_default_generic_fonts' );

		if ( $default_fonts ) {
			$default_fonts = ', ' . $default_fonts;
		}

		$fields['font_family'] = [
			'label' => _x( 'Family', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::FONT,
			'default' => '',
			'selector_value' => 'font-family: "{{VALUE}}"' . $default_fonts . ';',
		];

		$fields['font_size'] = [
			'label' => _x( 'Size', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem' ],
			'range' => [
				'px' => [
					'min' => 1,
					'max' => 200,
				],
			],
			'responsive' => true,
			'selector_value' => 'font-size: {{SIZE}}{{UNIT}}',
		];

		$typo_weight_options = [
			'' => __( 'Default', 'qazana' ),
		];

		foreach ( array_merge( [ 'normal', 'bold' ], range( 100, 900, 100 ) ) as $weight ) {
			$typo_weight_options[ $weight ] = ucfirst( $weight );
		}

		$fields['font_weight'] = [
			'label' => _x( 'Weight', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::SELECT,
			'default' => '',
			'options' => $typo_weight_options,
		];

		$fields['text_transform'] = [
			'label' => _x( 'Transform', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::SELECT,
			'default' => '',
			'options' => [
				'' => __( 'Default', 'qazana' ),
				'uppercase' => _x( 'Uppercase', 'Typography Control', 'qazana' ),
				'lowercase' => _x( 'Lowercase', 'Typography Control', 'qazana' ),
				'capitalize' => _x( 'Capitalize', 'Typography Control', 'qazana' ),
				'none' => _x( 'Normal', 'Typography Control', 'qazana' ),
			],
		];

		$fields['text_decoration'] = [
			'label' => _x( 'Decoration', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::SELECT,
			'default' => '',
			'options' => [
				'' 				=> __( 'Default', 'qazana' ),
				'underline' 	=> _x( 'Underline', 'Typography Control', 'qazana' ),
				'line-through' 	=> _x( 'Line through', 'Typography Control', 'qazana' ),
				'overline' 		=> _x( 'Overline', 'Typography Control', 'qazana' ),
				'none' 			=> _x( 'Normal', 'Typography Control', 'qazana' ),
			],
		];

		$fields['font_style'] = [
			'label' => _x( 'Style', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::SELECT,
			'default' => '',
			'options' => [
				'' => __( 'Default', 'qazana' ),
				'normal' => _x( 'Normal', 'Typography Control', 'qazana' ),
				'italic' => _x( 'Italic', 'Typography Control', 'qazana' ),
				'oblique' => _x( 'Oblique', 'Typography Control', 'qazana' ),
			],
		];

		$fields['line_height'] = [
			'label' => _x( 'Line-Height', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::SLIDER,
			'default' => [
				'unit' => 'em',
			],
			'range' => [
				'px' => [
					'min' => 1,
				],
			],
			'responsive' => true,
			'size_units' => [ 'px', 'em' ],
			'selector_value' => 'line-height: {{SIZE}}{{UNIT}}',
		];

		$fields['letter_spacing'] = [
			'label' => _x( 'Letter Spacing', 'Typography Control', 'qazana' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [
				'px' => [
					'max' => 20,
					'step' => 0.1,
				],
			],
			'responsive' => true,
			'selector_value' => 'letter-spacing: {{SIZE}}{{UNIT}}',
		];

		return $fields;
	}

	/**
	 * Prepare fields.
	 *
	 * Process typography control fields before adding them to `add_control()`.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $fields Typography control fields.
	 *
	 * @return array Processed fields.
	 */
	protected function prepare_fields( $fields ) {
		array_walk(
			$fields, function( &$field, $field_name ) {
				if ( in_array( $field_name, [ 'typography', 'popover_toggle' ] ) ) {
					return;
				}

				$selector_value = ! empty( $field['selector_value'] ) ? $field['selector_value'] : str_replace( '_', '-', $field_name ) . ': {{VALUE}};';

				$field['selectors'] = [
					'{{SELECTOR}}' => $selector_value,
				];

				$field['condition'] = [
					'typography' => 'custom',
				];
			}
		);

		return parent::prepare_fields( $fields );
	}

	/**
	 * Add group arguments to field.
	 *
	 * Register field arguments to typography control.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $control_id Typography control id.
	 * @param array  $field_args Typography control field arguments.
	 *
	 * @return array Field arguments.
	 */
	protected function add_group_args_to_field( $control_id, $field_args ) {
		$field_args = parent::add_group_args_to_field( $control_id, $field_args );

		$args = $this->get_args();

		if ( in_array( $control_id, self::get_scheme_fields_keys() ) && ! empty( $args['scheme'] ) ) {
			$field_args['scheme'] = [
				'type' => self::get_type(),
				'value' => $args['scheme'],
				'key' => $control_id,
			];
		}

		return $field_args;
	}

	protected function get_default_options() {
		return [
			'popover' => [
				'starter_name' => 'typography',
				'starter_title' => _x( 'Typography', 'Typography Control', 'qazana' ),
			],
		];
	}
}
