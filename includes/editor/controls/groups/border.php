<?php
namespace Qazana;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Border control.
 *
 * A base control for creating border control. Displays input fields to define
 * border type, border width and border color.
 *
 * Creating new control in the editor (inside `Widget_Base::_register_controls()`
 * method):
 *
 *    $this->add_group_control(
 *    	Group_Control_Border::get_type(),
 *    	[
 *    		'name' => 'border',
 *    		'selector' => '{{WRAPPER}} .wrapper',
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
class Group_Control_Border extends Group_Control_Base {

	/**
	 * Fields.
	 *
	 * Holds all the border control fields.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @var array Border control fields.
	 */
	protected static $fields;

	/**
	 * Retrieve type.
	 *
	 * Get border control type.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return string Control type.
	 */
	public static function get_type() {
		return 'border';
	}

	/**
	 * Init fields.
	 *
	 * Initialize border control fields.
	 *
	 * @since 1.2.2
	 * @access protected
	 *
	 * @return array Control fields.
	 */
	protected function init_fields() {
		$fields = [];

		$fields['border'] = [
			'label' => _x( 'Border Type', 'Border Control', 'qazana' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'' => __( 'None', 'qazana' ),
				'solid' => _x( 'Solid', 'Border Control', 'qazana' ),
				'double' => _x( 'Double', 'Border Control', 'qazana' ),
				'dotted' => _x( 'Dotted', 'Border Control', 'qazana' ),
				'dashed' => _x( 'Dashed', 'Border Control', 'qazana' ),
			],
			'selectors' => [
				'{{SELECTOR}}' => 'border-style: {{VALUE}};',
			],
		];

		$fields['width'] = [
			'label' => _x( 'Width', 'Border Control', 'qazana' ),
			'type' => Controls_Manager::DIMENSIONS,
			'selectors' => [
				'{{SELECTOR}}' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			'condition' => [
				'border!' => '',
			],
		];

		$fields['color'] = [
			'label' => _x( 'Color', 'Border Control', 'qazana' ),
			'type' => Controls_Manager::COLOR,
			'default' => '',
			'selectors' => [
				'{{SELECTOR}}' => 'border-color: {{VALUE}};',
			],
			'condition' => [
				'border!' => '',
			],
		];

		return $fields;
	}

	protected function get_default_options() {
		return [
			'popover' => [
				'starter_title' => _x( 'Border', 'Border Control', 'qazana' ),
				'toggle_type' => 'simple',
			],
		];
	}
}
