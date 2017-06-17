<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Widgets_Manager {
	/**
	 * @var Widget_Base[]
	 */
	private $_widget_types = null;

    public function __construct() {

        add_action( 'after_setup_theme', [ $this, 'require_files' ] );

        add_action( 'wp_ajax_builder_render_widget', [ $this, 'ajax_render_widget' ] );
        add_action( 'wp_ajax_builder_editor_get_wp_widget_form', [ $this, 'ajax_get_wp_widget_form' ] );
    }

    private function _init_widgets() {

        $this->_widget_types = [];

        $build_widgets_filename = [
            'common',

            'heading',
            'image',
            'text-editor',
            'video',
            'button',
            'divider',
            'spacer',
            'image-box',
            'icon',
            'icon-box',
            'image-gallery',
            'image-carousel',
            'icon-list',
            'counter',
            'progress',
            'testimonial',
            'tabs',
            'accordion',
            'toggle',
            'social-icons',
            'alert',
            'audio',
            'shortcode',
            'html',
            'menu-anchor',
            'sidebar',

        ];

        /**
         * Allow override of registered widget defaults
         *
         * @since 1.0.0
        *
        * @param array $build_widgets_filename.
        */
        $build_widgets_filename = apply_filters( 'builder/widgets/widget_filenames', $build_widgets_filename );

        // remove duplicates
        $build_widgets_filename = array_unique( $build_widgets_filename );

        foreach ( $build_widgets_filename as $widget_filename ) {

            if ( ! builder()->widget_loader->locate_widget( $widget_filename .'.php', false ) ) {
                continue;
            }

            $class_name = str_replace( '-', '_', $widget_filename );
            $class_name = __NAMESPACE__ . '\Widget_' . $class_name;

            $class_name = apply_filters( "builder/widgets/{$widget_filename}_class_name", $class_name, $widget_filename );

            if ( ! class_exists( $class_name ) ) {
                builder()->widget_loader->locate_widget( $widget_filename .'.php', true );
            }

            $widget_instance = new $class_name();

            $this->register_widget_type( $widget_instance );
        }

        $this->_register_wp_widgets();

        do_action( 'builder/widgets/widgets_registered' );
    }

    private function _register_wp_widgets() {

        global $wp_widget_factory;

        builder()->widget_loader->locate_widget( 'wordpress.php', true );

        /**
         * Allow override of allowed widgets
         *
         * @since 0.6.5
         *
         * @param array $allowed_widgets.
         */
        // Allow themes/plugins to filter out their widgets
		$black_list = apply_filters( 'builder/widgets/black_list', [] );

        foreach ( $wp_widget_factory->widgets as $widget_class => $widget_obj ) {

    		if ( in_array( $widget_class, $black_list ) ) {
    			continue;
    		}

            $builder_widget_class = __NAMESPACE__ . '\Widget_WordPress';
            $this->register_widget_type( new $builder_widget_class( array(), [ 'widget_name' => $widget_class ] ) );
        }
    }

    public function require_files() {

        $default_files = [];

		if ( ! class_exists( 'Builder\Controls_Stack' ) ) {
            $default_files[] = 'base/controls-stack.php';
        }

        if ( ! class_exists( 'Builder\Element_Base' ) ) {
            $default_files[] = 'base/element-base.php';
        }

        if ( ! class_exists( 'Builder\Widget_Base' ) ) {
            $default_files[] = 'base/widget-base.php';
        }

        $files = apply_filters( 'builder/widgets/require_files', $default_files );

        if ( is_array( $files ) ) {
            foreach ( $files as $file ) {
                builder()->widget_loader->locate_widget( $file, true );
            }
        }

    }

    public function register_widget_type( Widget_Base $widget ) {
        if ( is_null( $this->_widget_types ) ) {
            $this->_init_widgets();
        }

        $this->_widget_types[ $widget->get_name() ] = $widget;

        $this->_widget_types = apply_filters( 'builder/widgets/register_widget_type', $this->_widget_types, $this );

        return true;
    }

    public function unregister_widget_type( $name ) {
        if ( ! isset( $this->_widget_types[ $name ] ) ) {
            return false;
        }

        unset( $this->_widget_types[ $name ] );

        return true;
    }

    public function get_widget_types( $widget_name = null ) {
        if ( is_null( $this->_widget_types ) ) {
            $this->_init_widgets();
        }

		//sort alphabetically
		ksort( $this->_widget_types );

		if ( null !== $widget_name ) {
			return isset( $this->_widget_types[ $widget_name ] ) ? $this->_widget_types[ $widget_name ] : null;
		}

        return $this->_widget_types;
    }

    public function get_widget_types_config() {
        $config = [];

		foreach ( $this->get_widget_types() as $widget_key => $widget ) {
			if ( ! $widget->show_in_panel() ) {
				continue;
			}

			$config[ $widget_key ] = $widget->get_config();
		}

        return $config;
    }

    public function ajax_render_widget() {
        if ( empty( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'builder-editing' ) ) {
            wp_send_json_error( new \WP_Error( 'token_expired' ) );
        }

        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( new \WP_Error( 'no_post_id', 'No post_id' ) );
        }

        if ( ! User::is_current_user_can_edit( $_POST['post_id'] ) ) {
            wp_send_json_error( new \WP_Error( 'no_access' ) );
        }

        // Override the global $post for the render
        $GLOBALS['post'] = get_post( (int) $_POST['post_id'] );

        $data = json_decode( stripslashes( html_entity_decode( $_POST['data'] ) ), true );

        // Start buffering
        ob_start();

		$widget = builder()->elements_manager->create_element_instance( $data );

		if ( ! is_object( $widget ) ) {
			wp_send_json_error( new \WP_Error( 'no_widget', 'Widget not found' ) );
		}

        $widget->render_content();

        $render_html = ob_get_clean();

        wp_send_json_success([ 'render' => $render_html ]);
    }

    public function ajax_get_wp_widget_form() {
        if ( empty( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'builder-editing' ) ) {
            die;
        }

        $widget_type = $_POST['widget_type'];

        $widget_obj = $this->get_widget_types( $widget_type );

        if ( ! $widget_obj instanceof Widget_WordPress ) {
            wp_send_json_error();
        }

        $data = json_decode( stripslashes( html_entity_decode( $_POST['data'] ) ), true );

        wp_send_json_success( $widget_obj->get_form( $data ) );
    }

    public function render_widgets_content() {
        foreach ( $this->get_widget_types() as $widget ) {
            $widget->print_template();
        }
    }

	public function get_widgets_frontend_settings_keys() {
		$keys = [];

		foreach ( $this->get_widget_types() as $widget_type_name => $widget_type ) {
			$widget_type_keys = $widget_type->get_frontend_settings_keys();

			if ( $widget_type_keys ) {
				$keys[ $widget_type_name ] = $widget_type_keys;
			}
		}

		return $keys;
	}

}