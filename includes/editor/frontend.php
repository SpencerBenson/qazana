<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Frontend {

	private $_enqueue_google_fonts = [];
	private $_enqueue_google_early_access_fonts = [];

	private $_is_frontend_mode = false;
	private $_has_builder_in_page = false;

	/**
	 * @var Stylesheet
	 */
	private $stylesheet;

	public function init() {
		if ( builder()->editor->is_edit_mode() || builder()->preview->is_preview_mode() ) {
			return;
		}

		$this->_is_frontend_mode = true;
		$this->_has_builder_in_page = builder()->db->has_builder_in_post( get_the_ID() );

		$this->_init_stylesheet();

		add_action( 'wp_head', [ $this, 'print_css' ] );
		add_filter( 'body_class', [ $this, 'body_class' ] );

		if ( $this->_has_builder_in_page ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ], 999 );
		}

		add_action( 'wp_footer', [ $this, 'wp_footer' ] );

		// Add Edit with the Builder in Admin Bar
		add_action( 'admin_bar_menu', [ $this, 'add_menu_in_admin_bar' ], 200 );
	}

	private function _init_stylesheet() {
		$this->stylesheet = new Stylesheet();

		$breakpoints = Responsive::get_breakpoints();

		$this->stylesheet
			->add_device( 'mobile', $breakpoints['md'] - 1 )
			->add_device( 'tablet', $breakpoints['lg'] - 1 );
	}

	protected function _print_elements( $elements_data ) {
		foreach ( $elements_data as $element_data ) {
			$element = builder()->elements_manager->create_element_instance( $element_data );

			$element->print_element();
		}
	}

	public function body_class( $classes = [] ) {
		if ( is_singular() && 'builder' === builder()->db->get_edit_mode( get_the_ID() ) ) {
			$classes[] = 'builder-page';
		}
		return $classes;
	}

    public function enqueue_scripts() {

		do_action( 'builder/frontend/before_enqueue_scripts' );

		$suffix = Utils::is_script_debug() ? '' : '.min';

        wp_register_script(
            'waypoints',
            builder()->core_assets_url . 'lib/waypoints/waypoints' . $suffix . '.js',
            [
                'jquery',
            ],
            '2.0.2',
            true
        );

        wp_register_script(
            'jquery-numerator',
            builder()->core_assets_url . 'lib/jquery-numerator/jquery-numerator' . $suffix . '.js',
            [
                'jquery',
            ],
            '0.2.0',
            true
        );

        wp_register_script(
            'jquery-slick',
            builder()->core_assets_url . 'lib/slick/slick' . $suffix . '.js',
            [
                'jquery',
            ],
            '1.6.0',
            true
        );

        wp_register_script(
            'builder-frontend',
            builder()->core_assets_url . 'js/frontend' . $suffix . '.js',
            [
                'waypoints',
                'jquery-numerator',
            ],
            builder()->get_version(),
            true
        );

        wp_enqueue_script( 'builder-frontend' );

		$builder_frontend_config = [
			'isEditMode' => builder()->editor->is_edit_mode(),
			'stretchedSectionContainer' => get_option( 'builder_stretched_section_container', '' ),
			'google_api_key' => get_option( 'builder_google_maps_api_key', '' ),
			'is_rtl' => is_rtl(),
			'assets_url' => builder()->core_assets_url,
			'nonce' => wp_create_nonce( 'builder-frontend' ),
		];

		$elements_manager = builder()->elements_manager;

		$elements_frontend_keys = [
			'section' => $elements_manager->get_element_types( 'section' )->get_frontend_settings_keys(),
			'column' => $elements_manager->get_element_types( 'column' )->get_frontend_settings_keys(),
		];

		$elements_frontend_keys += builder()->widgets_manager->get_widgets_frontend_settings_keys();

		if ( builder()->editor->is_edit_mode() ) {
			$builder_frontend_config['elements'] = [
				'data' => (object) [],
				'keys' => $elements_frontend_keys,
			];
		}

		$builder_frontend_config = apply_filters( 'builder/frontend/localize_settings', $builder_frontend_config );

		wp_localize_script( 'builder-frontend', 'builderFrontendConfig', $builder_frontend_config );

		do_action( 'builder/frontend/after_enqueue_scripts' );

    }

    public function enqueue_styles() {
        $suffix = Utils::is_script_debug() ? '' : '.min';

        $direction_suffix = is_rtl() ? '-rtl' : '';

        wp_enqueue_style(
            'builder-icons',
            builder()->core_assets_url . 'lib/eicons/css/icons' . $suffix . '.css',
            [],
            builder()->get_version()
        );

        wp_register_style(
            'builder-frontend',
            builder()->core_assets_url . 'css/frontend' . $direction_suffix . $suffix . '.css',
            [
                'builder-icons',
                'font-awesome',
            ],
            builder()->get_version()
        );

		wp_enqueue_style( 'builder-frontend' );

		$css_file = new Post_CSS_File( get_the_ID() );
		$css_file->enqueue();

		do_action( 'builder/frontend/after_enqueue_styles' );

	}

	public function print_css() {
		$container_width = absint( get_option( 'builder_container_width' ) );

		if ( ! empty( $container_width ) ) {
			$this->stylesheet->add_rules( '.builder-section.builder-section-boxed > .builder-container', 'max-width:' . $container_width . 'px' );
		}

		$this->_parse_schemes_css_code();

		$css_code = $this->stylesheet;

		if ( empty( $css_code ) )
			return;

		?>
		<style id="builder-frontend-stylesheet"><?php echo $css_code; ?></style>
		<?php

		$this->print_google_fonts();
	}

	/**
	 * Handle style that do not printed in header
	 */
	public function wp_footer() {
		if ( ! $this->_has_builder_in_page ) {
			return;
		}

		$this->enqueue_styles();
		$this->enqueue_scripts();

		// TODO: add JS to append the css to the `head` tag
		$this->print_google_fonts();
	}

	public function print_google_fonts() {
		// Enqueue used fonts
		if ( ! empty( $this->_enqueue_google_fonts ) ) {
			foreach ( $this->_enqueue_google_fonts as &$font ) {
				$font = str_replace( ' ', '+', $font ) . ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';
			}

			$fonts_url = sprintf( 'https://fonts.googleapis.com/css?family=%s', implode( '|', $this->_enqueue_google_fonts ) );

			$subsets = [
				'ru_RU' => 'cyrillic',
				'bg_BG' => 'cyrillic',
				'he_IL' => 'hebrew',
				'el' => 'greek',
				'vi' => 'vietnamese',
				'uk' => 'cyrillic',
			];
			$locale = get_locale();

			if ( isset( $subsets[ $locale ] ) ) {
				$fonts_url .= '&subset=' . $subsets[ $locale ];
			}

			echo '<link rel="stylesheet" type="text/css" href="' . $fonts_url . '">';
			$this->_enqueue_google_fonts = [];
		}

		if ( ! empty( $this->_enqueue_google_early_access_fonts ) ) {
			foreach ( $this->_enqueue_google_early_access_fonts as $current_font ) {
				printf( '<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/earlyaccess/%s.css">', strtolower( str_replace( ' ', '', $current_font ) ) );
			}
			$this->_enqueue_google_early_access_fonts = [];
		}
	}
	public function add_enqueue_font( $font ) {
		switch ( Fonts::get_font_type( $font ) ) {
			case Fonts::GOOGLE :
				if ( ! in_array( $font, $this->_enqueue_google_fonts ) )
					$this->_enqueue_google_fonts[] = $font;
				break;

			case Fonts::EARLYACCESS :
				if ( ! in_array( $font, $this->_enqueue_google_early_access_fonts ) )
					$this->_enqueue_google_early_access_fonts[] = $font;
				break;
		}
	}

	protected function _parse_schemes_css_code() {
		foreach ( builder()->widgets_manager->get_widget_types() as $widget ) {
			$scheme_controls = $widget->get_scheme_controls();

			foreach ( $scheme_controls as $control ) {
				Post_CSS_File::add_control_rules( $this->stylesheet, $control, $widget->get_controls(), function ( $control ) {
					$scheme_value = builder()->schemes_manager->get_scheme_value( $control['scheme']['type'], $control['scheme']['value'] );

					if ( empty( $scheme_value ) ) {
						return null;
					}

					if ( ! empty( $control['scheme']['key'] ) ) {
						$scheme_value = $scheme_value[ $control['scheme']['key'] ];
					}

					if ( empty( $scheme_value ) ) {
						return null;
					}

					$control_obj = builder()->controls_manager->get_control( $control['type'] );

					if ( Controls_Manager::FONT === $control_obj->get_type() ) {
						$this->add_enqueue_font( $scheme_value );
					}

					return $scheme_value;
				}, [ '{{WRAPPER}}' ], [ '.builder-widget-' . $widget->get_name() ] );
			}
		}
	}

	public function apply_builder_in_content( $content ) {
		// Remove the filter itself in order to allow other `the_content` in the elements
		remove_filter( 'the_content', [ $this, 'apply_builder_in_content' ] );

		if ( ! $this->_is_frontend_mode )
			return $content;

		$post_id = get_the_ID();
		$builder_content = $this->get_builder_content( $post_id );

		if ( ! empty( $builder_content ) ) {
			$content = $builder_content;
		}

		// Add the filter again for other `the_content` calls
		add_filter( 'the_content', [ $this, 'apply_builder_in_content' ] );

		return $content;
	}

	public function get_builder_content( $post_id, $with_css = false ) {
		if ( post_password_required( $post_id ) ) {
			return '';
		}

		$edit_mode = builder()->db->get_edit_mode( $post_id );
		if ( 'builder' !== $edit_mode ) {
			return '';
		}

		$data = builder()->db->get_plain_editor( $post_id );
		$data = apply_filters( 'builder/frontend/builder_content_data', $data, $post_id );

		if ( empty( $data ) ) {
			return '';
		}

		$css_file = new Post_CSS_File( $post_id );
		$css_file->enqueue();

		ob_start();

		// Handle JS and Customizer requests, with css inline
		if ( is_customize_preview() || Utils::is_ajax() ) {
			$with_css = true;
		}

		if ( $with_css ) {
			echo '<style>' . $css_file->get_css() . '</style>';
		}

		?>
		<div class="builder builder-<?php echo $post_id; ?>">
			<div class="builder-inner">
				<div class="builder-section-wrap">
					<?php $this->_print_elements( $data ); ?>
				</div>
			</div>
		</div>
		<?php
		return apply_filters( 'builder/frontend/the_content', ob_get_clean() );
	}

	function add_menu_in_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {
		$post_id = get_the_ID();
		$is_not_builder_mode = ! is_singular() || ! User::is_current_user_can_edit( $post_id ) || 'builder' !== builder()->db->get_edit_mode( $post_id );

		if ( $is_not_builder_mode ) {
			return;
		}

		$wp_admin_bar->add_node( [
			'id' => 'builder_edit_page',
			'title' => __( 'Edit with Builder', 'builder' ),
			'href' => Utils::get_edit_link( $post_id ),
		] );
	}

	public function get_builder_content_for_display( $post_id ) {
		if ( ! get_post( $post_id ) ) {
			return '';
		}

		// Avoid recursion
		if ( get_the_ID() === (int) $post_id ) {
			$content = '';
			if ( builder()->editor->is_edit_mode() ) {
				$content = '<div class="builder-alert builder-alert-danger">' . __( 'Invalid Data: The Template ID cannot be the same as the currently edited template. Please choose a different one.', 'builder' ) . '</div>';
			}

			return $content;
		}

		// Set edit mode as false, so don't render settings and etc. use the $is_edit_mode to indicate if we need the css inline
		$is_edit_mode = builder()->editor->is_edit_mode();
		builder()->editor->set_edit_mode( false );

		// Change the global post to current library post, so widgets can use `get_the_ID` and other post data
		if ( isset( $GLOBALS['post'] ) ) {
			$global_post = $GLOBALS['post'];
		}

		$GLOBALS['post'] = get_post( $post_id );

		$content = $this->get_builder_content( $post_id, $is_edit_mode );

		if ( ! empty( $content ) ) {
			$this->_has_builder_in_page = true;
		}

		// Restore global post
		if ( isset( $global_post ) ) {
			$GLOBALS['post'] = $global_post;
		} else {
			unset( $GLOBALS['post'] );
		}

		// Restore edit mode state
		builder()->editor->set_edit_mode( $is_edit_mode );

		return $content;
	}

	public function __construct() {
		// We don't need this class in admin side, but in AJAX requests
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'init' ] );
		add_filter( 'the_content', [ $this, 'apply_builder_in_content' ] );
	}
}