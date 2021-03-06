<?php
namespace Qazana;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base group control.
 *
 * A base control for creating group control.
 *
 * @since 1.0.0
 * @abstract
 */
abstract class Group_Control_Base implements Group_Control_Interface {

	/**
	 * Arguments.
	 *
	 * Holds all the base group control arguments.
	 *
	 * @access private
	 *
	 * @var array Group control arguments.
	 */
	private $args = [];

	private $options;

	final public function get_options( $option ) {
		if ( null === $this->options ) {
			$this->init_options();
		}

		if ( $option ) {
			if ( isset( $this->options[ $option ] ) ) {
				return $this->options[ $option ];
			}

			return null;
		}

		return $this->options;
	}

	/**
	 * Add new controls to stack.
	 *
	 * Register multiple controls to allow the user to set/update data.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Controls_Stack $element   The element stack.
	 * @param array          $user_args The control arguments defined by the user.
	 * @param array          $options   Optional. The element options. Default is
	 *                                  an empty array.
	 */
	final public function add_controls( Controls_Stack $element, array $user_args, array $options = [] ) {
		$this->init_args( $user_args );

		// Filter which controls to display
		$filtered_fields = $this->filter_fields();
		$filtered_fields = $this->prepare_fields( $filtered_fields );

		// For php < 7
		reset( $filtered_fields );

		if ( $this->get_options( 'popover' ) ) {
			$filtered_fields = $this->set_popover( $filtered_fields );
		}

		if ( isset( $this->args['separator'] ) ) {
			$filtered_fields[ key( $filtered_fields ) ]['separator'] = $this->args['separator'];
		}

		$has_injection = false;

		if ( ! empty( $options['position'] ) ) {
			$has_injection = true;

			$element->start_injection( $options['position'] );

			unset( $options['position'] );
		}

		foreach ( $filtered_fields as $field_id => $field_args ) {
			// Add the global group args to the control
			$field_args = $this->add_group_args_to_field( $field_id, $field_args );

			// Register the control
			$id = $this->get_controls_prefix() . $field_id;

			if ( ! empty( $field_args['responsive'] ) ) {
				unset( $field_args['responsive'] );

				$element->add_responsive_control( $id, $field_args, $options );
			} else {
				$element->add_control( $id , $field_args, $options );
			}
		}

		if ( $has_injection ) {
			$element->end_injection();
		}
	}

	final public function remove_controls( Controls_Stack $element ) {

		// Filter witch controls to display
		$fields = $this->get_fields();

		foreach ( $fields as $field_id => $field_args ) {

			// Register the control
			$id = $this->get_controls_prefix() . $field_id;

			$element->remove_control( $id );
		}
	}

	final public function get_args() {
		return $this->args;
	}

	/**
	 * Retrieve fields.
	 *
	 * Get group control fields.
	 *
	 * @since 1.2.2
	 * @access public
	 *
	 * @return array Control fields.
	 */
	final public function get_fields() {
		// TODO: Temp - compatibility for posts group
		if ( method_exists( $this, '_get_controls' ) ) {
			return $this->_get_controls( $this->get_args() );
		}

		if ( null === static::$fields ) {
			static::$fields = $this->init_fields();
		}

		return static::$fields;
	}

	/**
	 * Retrieve controls prefix.
	 *
	 * Get the prefix of the group control, which is `{{ControlName}}_`.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Control prefix.
	 */
	public function get_controls_prefix() {
		return ! empty($this->args['name']) ? $this->args['name'] . '_' : '_';
	}

	/**
	 * Retrieve group control classes.
	 *
	 * Get the classes of the group control.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Group control classes.
	 */
	public function get_base_group_classes() {
		return 'qazana-group-control-' . static::get_type() . ' qazana-group-control';
	}

	abstract protected function init_fields();
	/**
	 * Init fields.
	 *
	 * Initialize group control fields.
	 *
	 * @since 1.2.2
	 * @access protected
	 */
	protected function get_default_options() {
		return [];
	}

	/**
	 * Retrieve child default arguments.
	 *
	 * Get the default arguments for all the child controls for a specific group
	 * control.
	 *
	 * @since 1.2.2
	 * @access protected
	 *
	 * @return array Default arguments for all the child controls.
	 */
	protected function get_child_default_args() {
		return [];
	}

	/**
	 * Filter fields.
	 *
	 * Filter which controls to display, using `include`, `exclude` and the
	 * `condition` arguments.
	 *
	 * @since 1.2.2
	 * @access protected
	 *
	 * @return array Control fields.
	 */
	protected function filter_fields() {
		$args = $this->get_args();

		$fields = $this->get_fields();

		if ( ! empty( $args['include'] ) ) {
			$fields = array_intersect_key( $fields, array_flip( $args['include'] ) );
		}

		if ( ! empty( $args['exclude'] ) ) {
			$fields = array_diff_key( $fields, array_flip( $args['exclude'] ) );
		}

		return $fields;
	}

	/**
	 * Add group arguments to field.
	 *
	 * Register field arguments to group control.
	 *
	 * @since 1.2.2
	 * @access protected
	 *
	 * @param string $control_id Group control id.
	 * @param array  $field_args Group control field arguments.
	 *
	 * @return array
	 */
	protected function add_group_args_to_field( $control_id, $field_args ) {
		$args = $this->get_args();

		if ( ! empty( $args['tab'] ) ) {
			$field_args['tab'] = $args['tab'];
		}

		if ( ! empty( $args['section'] ) ) {
			$field_args['section'] = $args['section'];
		}

		$classes = $this->get_base_group_classes() . ' qazana-group-control-' . $control_id;

		$field_args['classes'] = ! empty( $field_args['classes'] ) ? $field_args['classes'] . ' ' . $classes : $classes;

		// add defaults
		if ( ! empty( $args['defaults'][$control_id] ) ) {
			$field_args['default'] = $args['defaults'][$control_id];
		}

		if ( ! empty( $args['condition'] ) ) {
			if ( empty( $field_args['condition'] ) ) {
				$field_args['condition'] = [];
			}

			$field_args['condition'] += $args['condition'];
		}

		return $field_args;
	}

	/**
	 * Prepare fields.
	 *
	 * Process group control fields before adding them to `add_control()`.
	 *
	 * @since 1.2.2
	 * @access protected
	 *
	 * @param array $fields Group control fields.
	 *
	 * @return array Processed fields.
	 */
	protected function prepare_fields( $fields ) {
		foreach ( $fields as $field_key => &$field ) {
			if ( ! empty( $field['condition'] ) ) {
				$field = $this->add_conditions_prefix( $field );
			}

			if ( ! empty( $field['selectors'] ) ) {
				$field['selectors'] = $this->handle_selectors( $field['selectors'] );
			}

			if ( isset( $this->args['fields_options']['__all'] ) ) {
				$field = array_merge( $field, $this->args['fields_options']['__all'] );
			}

			if ( isset( $this->args['fields_options'][ $field_key ] ) ) {
				$field = array_merge( $field, $this->args['fields_options'][ $field_key ] );
			}
		}

		return $fields;
	}

	private function init_options() {
		$default_options = [
			'popover' => [
				'starter_name' => 'popover_toggle',
				'starter_value' => 'custom',
				'starter_title' => '',
				'toggle_type' => 'switcher',
				'toggle_title' => __( 'Set', 'qazana' ),
			],
		];

		$this->options = array_replace_recursive( $default_options, $this->get_default_options() );
	}

	/**
	 * Init arguments.
	 *
	 * Initializing group control base class.
	 *
	 * @since 1.2.2
	 * @access private
	 *
	 * @param array $args Group control settings value.
	 *
	 * @return array Control default settings.
	 */
	private function init_args( $args ) {
		$this->args = array_merge( $this->get_default_args(), $this->get_child_default_args(), $args );
	}

	/**
	 * Retrieve default arguments.
	 *
	 * Get the default arguments of the group control. Used to return the
	 * default arguments while initializing the group control.
	 *
	 * @since 1.2.2
	 * @access private
	 *
	 * @return array Control default arguments.
	 */
	private function get_default_args() {
		return [
			'default' => '',
			'selector' => '{{WRAPPER}}',
			'fields_options' => [],
		];
	}

	/**
	 * Add condition prefix.
	 *
	 * Used to add the group prefix to controls with conditions, to
	 * distinguish them from other controls with the same name.
	 *
	 * This way Qazana can apply condition logic to a specific control in a
	 * group control.
	 *
	 * @since 1.2.0
	 * @access private
	 *
	 * @param array $field Group control field.
	 *
	 * @return array Group control field.
	 */
	private function add_conditions_prefix( $field ) {
		$controls_prefix = $this->get_controls_prefix();

		$prefixed_condition_keys = array_map(
			function( $key ) use ( $controls_prefix ) {
				return $controls_prefix . $key;
			},
			array_keys( $field['condition'] )
		);

		$field['condition'] = array_combine(
			$prefixed_condition_keys,
			$field['condition']
		);

		return $field;
	}

	/**
	 * Handle selectors.
	 *
	 * Used to process the CSS selector of group control fields. When using
	 * group control, Qazana needs to apply the selector to different fields.
	 * This method handels the process.
	 *
	 * In addition, it handels selector values from other fields and process the
	 * css.
	 *
	 * @since 1.2.2
	 * @access private
	 *
	 * @return array Processed selectors.
	 */
	private function handle_selectors( $selectors ) {
		$args = $this->get_args();

		$selectors = array_combine(
			array_map(
				function( $key ) use ( $args ) {
						return str_replace( '{{SELECTOR}}', $args['selector'], $key );
				}, array_keys( $selectors )
			),
			$selectors
		);

		if ( ! $selectors ) {
			return $selectors;
		}

		$controls_prefix = $this->get_controls_prefix();

		foreach ( $selectors as &$selector ) {
			$selector = preg_replace_callback(
				'/(?:\{\{)\K[^.}]+(?=\.[^}]*}})/', function( $matches ) use ( $controls_prefix ) {
					return $controls_prefix . $matches[0];
				}, $selector
			);
		}

		return $selectors;
	}

	private function set_popover( array $fields ) {
		$popover_options = $this->get_options( 'popover' );

		$fields[ key( $fields ) ]['popover']['start'] = true;

		$popover_toggle_field = [
			$popover_options['starter_name'] => [
				'type' => Controls_Manager::POPOVER_TOGGLE,
				'label' => $popover_options['starter_title'],
				'toggle_type' => $popover_options['toggle_type'],
				'toggle_title' => $popover_options['toggle_title'],
				'return_value' => $popover_options['starter_value'],
			]
		];

		$fields = $popover_toggle_field + $fields;

		end( $fields );

		$fields[ key( $fields ) ]['popover']['end'] = true;

		reset( $fields );

		return $fields;
	}
}
