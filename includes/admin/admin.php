<?php

/**
 * Main Builder Admin Class.
 */
namespace Builder;

/**
 * Loads Builder plugin admin area.
 *
 * @since 1.0.0
 */
class Admin {

    /** Directory *************************************************************/

    /**
     * @var string Path to the Builder admin directory
     */
    public $admin_dir = '';

    /** URLs ******************************************************************/

    /**
     * @var string URL to the Builder admin directory
     */
    public $admin_url = '';

    /**
     * @var string URL to the Builder images directory
     */
    public $images_url = '';

    /**
     * @var string URL to the Builder admin styles directory
     */
    public $styles_url = '';

    /**
     * @var string URL to the Builder admin css directory
     */
    public $css_url = '';

    /**
     * @var string URL to the Builder admin js directory
     */
    public $js_url = '';

    /** Capability ************************************************************/

    /**
     * @var bool Minimum capability to access Tools and Settings
     */
    public $minimum_capability = 'manage_options'; //'keep_gate'; //patch till user management is fully functional

    /** Separator *************************************************************/

    /**
     * @var bool Whether or not to add an extra top level menu separator
     */
    public $show_separator = false;

    /** Admin Settings ************************************************************/

    /**
     * @var string Settings page slug
     */
    private $general_settings_key = '';

    /**
     * @var array Settings tabs
     */
    private $plugin_settings_tabs = array();

    /** Functions *************************************************************/

    /**
     * The main Builder admin loader.
     *
     * @since 1.0.0
     *
     * @uses Builder_Admin::setup_globals() Setup the globals needed
     * @uses Builder_Admin::includes() Include the required files
     * @uses Builder_Admin::setup_actions() Setup the hooks and actions
     */
    public function __construct() {

        $this->setup_globals();
        $this->includes();
        $this->setup_actions();

    }

    /**
     * Admin globals.
     *
     * @since 1.0.0
     */
    private function setup_globals() {

        $builder = builder();

        $this->admin_dir    = trailingslashit( $builder->includes_dir . 'admin' ); // Admin path
        $this->admin_url    = trailingslashit( $builder->core_assets_url ); // Admin url
        $this->images_url   = trailingslashit( $this->admin_url . 'images' ); // Admin images URL
        $this->styles_url   = trailingslashit( $this->admin_url . 'styles' ); // Admin styles URL
        $this->css_url      = trailingslashit( $this->admin_url . 'css' ); // Admin css URL
        $this->js_url       = trailingslashit( $this->admin_url . 'js' ); // Admin js URL

    }

    /**
     * Include required files.
     *
     * @since 1.0.0
     */
    private function includes() {
        require( $this->admin_dir . 'upgrades.php' );
        require( $this->admin_dir . 'editor.php' );
        require( $this->admin_dir . 'api.php' );
        require( $this->admin_dir . 'settings/panel.php' );
        require( $this->admin_dir . 'settings/system-info/main.php' );
        require( $this->admin_dir . 'tracker.php' );
    }

    /**
     * Setup the admin hooks, actions and filters.
     *
     * @since 1.0.0
     *
     * @uses add_action() To add various actions
     * @uses add_filter() To add various filters
     */
    private function setup_actions() {

        // Bail to prevent interfering with the deactivation process
        if ( builder_is_deactivation() ) {
            return;
        }

        /* General Actions ***************************************************/

        add_action( 'builder_admin_menu',              array( $this, 'admin_menus' ) ); // Add menu item to settings menu
        add_action( 'builder_admin_notices',           array( $this, 'activation_notice' ) ); // Add notice if not using a Builder theme
        //add_action( 'builder_register_admin_settings', array( $this, 'register_admin_settings' ) ); // Add settings
        add_action( 'builder_activation',              array( $this, 'new_install' ) ); // Add menu item to settings menu

        add_action( 'admin_enqueue_scripts',                    array( $this, 'enqueue_styles' ) ); // Add enqueued CSS
        add_action( 'admin_enqueue_scripts',                    array( $this, 'enqueue_scripts' ) ); // Add enqueued JS

        add_action( 'wp_dashboard_setup',                       array( $this, 'dashboard_widget_right_now' ) ); // Builder 'Right now' Dashboard widget
        add_action( 'admin_bar_menu',                           array( $this, 'admin_bar_about_link' ), 15 ); // Add a link to Builder about page to the admin bar

        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ] );

        // Ajax
        add_action( 'wp_ajax_builder_deactivate_feedback', [ $this, 'ajax_builder_deactivate_feedback' ] );

        /* Filters ***********************************************************/

        // Modify Builder's admin links
        add_filter( 'plugin_action_links_' . builder()->basename, array( $this, 'modify_plugin_action_links' )  );

        /* Network Admin *****************************************************/

        // Add menu item to settings menu
        add_action( 'network_admin_menu',  array( $this, 'network_admin_menus' ) );

        /* Dependencies ******************************************************/
        add_action( 'builder_admin_loaded',  array( $this, 'init_classes' ) );

        // Allow plugins to modify these actions
        do_action_ref_array( 'builder_admin_loaded', array( &$this ) );
    }

    /**
     * Load classes
     *
     * @since 1.0.0
     *
     * @return [type] [description]
     */
    public function init_classes() {
        $this->editor_admin         = new Editor_Admin();
        $this->settings             = new Settings_Panel();
        $this->system_info          = new System_Info\Main();
        $this->admin_api            = new Admin_Api();
        $this->admin_tracker        = new Admin_Tracker();

    }

    /**
     * Add the admin menus.
     *
     * @since 1.0.0
     *
     * @uses add_management_page() To add the Recount page in Tools section
     * @uses add_options_page() To add the Builder settings page in Settings
     *                           section
     */
    public function admin_menus() {

        // Are settings enabled?
            add_options_page(
                __( 'Builder',  'builder' ),
                __( 'Builder',  'builder' ),
                $this->minimum_capability,
                'builder',
                array( &$this, 'plugin_options_page' )
            );

            // These are later removed in admin_head
            // About
            add_dashboard_page(
                __( 'Welcome to Builder',  'builder' ),
                __( 'Welcome to Builder',  'builder' ),
                $this->minimum_capability,
                'builder-about',
                array( $this, 'about_screen' )
            );

        // Bail if plugin is not network activated
        if ( ! is_plugin_active_for_network( builder()->basename ) ) {
            return;
        }

        add_submenu_page(
            'index.php',
            __( 'Update Builder', 'builder' ),
            __( 'Update Builder', 'builder' ),
            'manage_network',
            'builder-update',
            array( $this, 'update_screen' )
        );
    }

    /**
     * Add the network admin menus.
     *
     * @since 1.0.0
     *
     * @uses add_submenu_page() To add the Update Builder page in Updates
     */
    public function network_admin_menus() {

        // Bail if plugin is not network activated
        if ( ! is_plugin_active_for_network( builder()->basename ) ) {
            return;
        }

        add_submenu_page( 'upgrade.php', __( 'Update Builder', 'builder' ), __( 'Update Builder', 'builder' ), 'manage_network', 'builder-update', array( $this, 'network_update_screen' ) );
    }

    /**
     * If this is a new installation, create some initial builder content.
     *
     * @since 1.0.0
     *
     * @return type
     */
    public static function new_install() {

        if ( ! builder_is_install() ) {
            return;
        }

        builder_create_initial_content();
    }

    /**
     * Register the settings.
     *
     * @since 1.0.0
     *
     * @uses add_settings_section() To add our own settings section
     * @uses add_settings_field() To add various settings fields
     * @uses register_setting() To register various settings
     *
     * @todo Put fields into multidimensional array
     */
    public function register_admin_settings() {

        // Bail if no sections available
        $sections = builder_admin_get_settings_sections();

        if ( empty( $sections ) ) {
            return false;
        }

        // Are we using settings integration?
        //$settings_integration = true;

        // Loop through sections
        foreach ( ( array ) $sections as $section_id => $section ) {

            // Only proceed if current user can see this section
            if ( ! current_user_can( $section_id ) ) {
                continue;
            }

            // Only add section and fields if section has fields
            $fields = builder_admin_get_settings_fields_for_section( $section_id );
            if ( empty( $fields ) ) {
                continue;
            }

            // Toggle the section if core integration is on
            if ( ( true === $settings_integration ) && ! empty( $section['page'] ) ) {
                $page = $section['page'];
            } else {
                $page = builder()->slug;

                $this->general_settings_key = $section_id;
                $this->plugin_settings_tabs[$this->general_settings_key] = $section['title'];
                $page = $this->general_settings_key;
            }

            // Add the section
            add_settings_section( $section_id, $section['title'], $section['callback'], $page );

            // Loop through fields for this section
            foreach ( ( array ) $fields as $field_id => $field ) {

                // Add the field
                if ( !empty( $field['callback'] ) && !empty( $field['title'] ) ) {
                    add_settings_field( $field_id, $field['title'], $field['callback'], $page, $section_id, $field['args'] );
                }

                // Register the setting
                register_setting( $page, $field_id, $field['sanitize_callback'] );
            }
        }
    }

    /**
     * Admin area activation notice.
     *
     * Shows a nag message in admin area about the theme not supporting Builder
     *
     * @since 1.0.0
     *
     * @uses current_user_can() To check notice should be displayed.
     */
    public function activation_notice() { }

    /**
     * Add Settings link to plugins area.
     *
     * @since 1.0.0
     *
     * @param array  $links Links array in which we would prepend our link
     * @param string $file  Current plugin basename
     *
     * @return array Processed links
     */
    public static function modify_plugin_action_links( $links  ) {

        // New links to merge into existing links
        $new_links = array();

        // Settings page link
        $new_links['settings'] = '<a href="'. esc_url( add_query_arg( array( 'page' => 'builder' ), admin_url( 'options-general.php' ) ) ).'">'.esc_html__( 'Settings', 'builder' ).'</a>';

        // About page link
        $new_links['about'] = '<a href="'. esc_url( add_query_arg( array( 'page' => 'builder-about' ), admin_url( 'index.php' ) ) ).'">'.esc_html__( 'About',    'builder' ).'</a>';

        // Add a few links to the existing links array
        return array_merge( $links, $new_links );
    }

    /**
     * Add the 'Right now in Builder' dashboard widget.
     *
     * @since 1.0.0
     *
     * @uses wp_add_dashboard_widget() To add the dashboard widget
     */
    public static function dashboard_widget_right_now() {
        //wp_add_dashboard_widget( 'builder-dashboard-right-now', __( 'Right Now in Builder', 'builder' ), 'builder_dashboard_widget_right_now' );
    }

    /**
     * Add a link to Builder about page to the admin bar.
     *
     * @since 1.0.0
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function admin_bar_about_link( $wp_admin_bar ) {

        if ( is_user_logged_in() ) {

            $wp_admin_bar->add_menu( array(
                'parent' => 'wp-logo',
                'id' => 'builder-about',
                'title' => esc_html__( 'About Builder', 'builder' ),
                'href' => add_query_arg( array( 'page' => 'builder-about' ), admin_url( 'index.php' ) ),
            ) );

        }
    }

    /**
     * Enqueue any admin scripts we might need.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        // Get the version to use for JS
        $version = builder_get_version();

        if ( in_array( get_current_screen()->id, [ 'plugins', 'plugins-network' ] ) ) {
            add_action( 'admin_footer', [ $this, 'print_deactivate_feedback_dialog' ] );
            $this->enqueue_feedback_dialog_scripts();
        }
    }

    /**
     * Enqueue any admin scripts we might need.
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        $suffix = Utils::is_script_debug() ? '' : '.min';

        wp_enqueue_style( 'builder-admin-app', $this->css_url . 'admin'. $suffix .'.css', array( 'dashicons' ), builder_get_version() );
    }

    /** About *****************************************************************/

    /**
     * Output the about screen.
     *
     * @since 1.0.0
     */
    public function about_screen() {

        list( $display_version ) = explode( '-', builder_get_version() ); ?>

        <div class="wrap about-wrap">
            <h1><?php printf( esc_html__( 'Welcome to Builder %s', 'builder' ), $display_version ); ?></h1>
            <div class="about-text"><?php printf( esc_html__( 'Thank you for updating.', 'builder' ), $display_version ); ?></div>

            <h2 class="nav-tab-wrapper">
                <a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'builder-about' ), 'index.php' ) ) ); ?>">
                    <?php esc_html_e( 'What&#8217;s New', 'builder' ); ?>
                </a><a class="nav-tab" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'builder-credits' ), 'index.php' ) ) ); ?>">
                    <?php esc_html_e( 'Credits', 'builder' ); ?>
                </a>
            </h2>

            <div class="return-to-dashboard">
                <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'builder' ), 'options-general.php' ) ) ); ?>"><?php esc_html_e( 'Go to builder Settings', 'builder' ); ?></a>
            </div>

        </div>

        <?php
    }

    /** Updaters **************************************************************/

    /**
     * Update all Builder Builder across all sites.
     *
     * @since 1.0.0
     *
     * @global WPDB $wpdb
     *
     * @uses get_blog_option()
     * @uses wp_remote_get()
     */
    public static function update_screen() {
    }

    /**
     * Update all Builder Builder across all sites.
     *
     * @since 1.0.0
     *
     * @global WPDB $wpdb
     *
     * @uses get_blog_option()
     * @uses wp_remote_get()
     */
    public static function network_update_screen() {
    }

    /** Admin Settings UI **************************************************************/

    /*
     * Plugin Options page rendering goes here, checks
     * for active tab and replaces key with the related
     * settings key. Uses the plugin_options_tabs method
     * to render the tabs.
     */
    public function plugin_options_page() {
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key; ?>

        <div class="wrap">

            <?php $this->plugin_options_tabs(); ?>

            <form method="post" action="options.php">

                <?php wp_nonce_field( 'update-options' ); ?>

                <?php settings_fields( $tab ); ?>

                <?php do_settings_sections( $tab ); ?>

                <?php submit_button(); ?>

            </form>

        </div>

        <?php

    }

    /*
     * Renders our tabs in the plugin options page,
     * walks through the object's tabs array and prints
     * them one by one. Provides the heading for the
     * plugin_options_page method.
     */
    public function plugin_options_tabs() {
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;

        screen_icon();

        echo '<h2 class="nav-tab-wrapper">';

        foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';

            echo '<a class="nav-tab '.$active.'" href="?page=builder&tab='.esc_attr( $tab_key ).'">'.$tab_caption.'</a>';
        }

        echo '</h2>';
    }

    public function admin_notices() {
        $upgrade_notice = $this->admin_api->get_upgrade_notice();
        if ( empty( $upgrade_notice ) )
            return;

        if ( ! current_user_can( 'update_plugins' ) )
            return;

        if ( ! in_array( get_current_screen()->id, [ 'toplevel_page_builder', 'edit-builder_library', 'builder_page_builder-system-info', 'dashboard' ] ) ) {
            return;
        }

        // Check if have any upgrades
        $update_plugins = get_site_transient( 'update_plugins' );
        if ( empty( $update_plugins ) || empty( $update_plugins->response[ builder()->basename ] ) || empty( $update_plugins->response[ builder()->basename ]->package ) ) {
            return;
        }
        $product = $update_plugins->response[ builder()->basename ];

        // Check if have upgrade notices to show
        if ( version_compare( builder()->get_version(), $upgrade_notice['version'], '>=' ) )
            return;

        $notice_id = 'upgrade_notice_' . $upgrade_notice['version'];
        if ( User::is_user_notice_viewed( $notice_id ) )
            return;

        $details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $product->slug . '&section=changelog&TB_iframe=true&width=600&height=800' );
        $upgrade_url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . builder()->basename ), 'upgrade-plugin_' . builder()->basename );
        ?>
        <div class="notice updated is-dismissible builder-message builder-message-dismissed" data-notice_id="<?php echo esc_attr( $notice_id ); ?>">
            <div class="builder-message-inner">
                <div class="builder-message-icon">
                    <i class="eicon-builder-square"></i>
                </div>
                <div class="builder-message-content">
                    <h3><?php _e( 'New in Builder', 'builder' ); ?></h3>
                    <p><?php
                        printf(
                            /* translators: 1: details URL, 2: accessibility text, 3: version number, 4: update URL, 5: accessibility text */
                            __( 'There is a new version of Builder Page Builder available. <a href="%1$s" class="thickbox open-plugin-details-modal" aria-label="%2$s">View version %3$s details</a> or <a href="%4$s" class="update-link" aria-label="%5$s">update now</a>.', 'builder' ),
                            esc_url( $details_url ),
                            esc_attr(
                                sprintf(
                                    /* translators: %s: version number */
                                    __( 'View Builder version %s details', 'builder' ),
                                    $product->new_version
                                )
                            ),
                            $product->new_version,
                            esc_url( $upgrade_url ),
                            esc_attr( __( 'Update Now', 'builder' ) )
                        );
                        ?></p>
                </div>
                <div class="builder-update-now">
                    <a class="button builder-button" href="<?php echo $upgrade_url; ?>"><i class="dashicons dashicons-update"></i><?php _e( 'Update Now', 'builder' ); ?></a>
                </div>
            </div>
        </div>
        <?php
    }

    public function admin_footer_text( $footer_text ) {
        $current_screen = get_current_screen();
        $is_builder_screen = ( $current_screen && false !== strpos( $current_screen->base, 'builder' ) );

        if ( $is_builder_screen ) {
            $footer_text = sprintf(
                /* translators: %s: link to plugin review */
                __( 'Enjoyed <strong>Builder</strong>? Please leave us a %s rating. We really appreciate your support!', 'builder' ),
                '<a href="https://wordpress.org/support/view/plugin-reviews/builder?filter=5#postform" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
            );
        }

        return $footer_text;
    }

    public function enqueue_feedback_dialog_scripts() {
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_register_script(
            'builder-dialog',
            builder()->core_assets_url . 'lib/dialog/dialog' . $suffix . '.js',
            [
                'jquery-ui-position',
            ],
            '3.0.0',
            true
        );

        wp_register_script(
            'builder-admin-feedback',
            builder()->core_assets_url . 'js/admin-feedback' . $suffix . '.js',
            [
                'underscore',
                'builder-dialog',
            ],
            builder()->get_version(),
            true
        );

        wp_enqueue_script( 'builder-admin-feedback' );

        wp_localize_script(
            'builder-admin-feedback',
            'BuilderAdminFeedbackArgs',
            [
                'is_tracker_opted_in' => Admin_Tracker::is_allow_track(),
                'i18n' => [
                    'submit_n_deactivate' => __( 'Submit & Deactivate', 'builder' ),
                    'skip_n_deactivate' => __( 'Skip & Deactivate', 'builder' ),
                ],
            ]
        );
    }

    public function print_deactivate_feedback_dialog() {
        $deactivate_reasons = [
            'no_longer_needed' => [
                'title' => __( 'I no longer need the plugin', 'builder' ),
                'input_placeholder' => '',
            ],
            'found_a_better_plugin' => [
                'title' => __( 'I found a better plugin', 'builder' ),
                'input_placeholder' => __( 'Please share which plugin', 'builder' ),
            ],
            'couldnt_get_the_plugin_to_work' => [
                'title' => __( 'I couldn\'t get the plugin to work', 'builder' ),
                'input_placeholder' => '',
            ],
            'temporary_deactivation' => [
                'title' => __( 'It\'s a temporary deactivation', 'builder' ),
                'input_placeholder' => '',
            ],
            'other' => [
                'title' => __( 'Other', 'builder' ),
                'input_placeholder' => __( 'Please share the reason', 'builder' ),
            ],
        ];

        ?>
        <div id="builder-deactivate-feedback-dialog-wrapper">
            <div id="builder-deactivate-feedback-dialog-header">
                <i class="eicon-builder-square"></i>
                <span id="builder-deactivate-feedback-dialog-header-title"><?php _e( 'Quick Feedback', 'builder' ); ?></span>
            </div>
            <form id="builder-deactivate-feedback-dialog-form" method="post">
                <?php
                wp_nonce_field( '_builder_deactivate_feedback_nonce' );
                ?>
                <input type="hidden" name="action" value="builder_deactivate_feedback" />

                <div id="builder-deactivate-feedback-dialog-form-caption"><?php _e( 'If you have a moment, please share why you are deactivating Builder:', 'builder' ); ?></div>
                <div id="builder-deactivate-feedback-dialog-form-body">
                    <?php foreach ( $deactivate_reasons as $reason_key => $reason ) : ?>
                        <div class="builder-deactivate-feedback-dialog-input-wrapper">
                            <input id="builder-deactivate-feedback-<?php echo esc_attr( $reason_key ); ?>" class="builder-deactivate-feedback-dialog-input" type="radio" name="reason_key" value="<?php echo esc_attr( $reason_key ); ?>" />
                            <label for="builder-deactivate-feedback-<?php echo esc_attr( $reason_key ); ?>" class="builder-deactivate-feedback-dialog-label"><?php echo $reason['title']; ?></label>
                            <?php if ( ! empty( $reason['input_placeholder'] ) ) : ?>
                                <input class="builder-feedback-text" type="text" name="reason_<?php echo esc_attr( $reason_key ); ?>" placeholder="<?php echo esc_attr( $reason['input_placeholder'] ); ?>" />
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
        <?php
    }

    public function ajax_builder_deactivate_feedback() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], '_builder_deactivate_feedback_nonce' ) ) {
            wp_send_json_error();
        }

        $reason_text = $reason_key = '';

        if ( ! empty( $_POST['reason_key'] ) )
            $reason_key = $_POST['reason_key'];

        if ( ! empty( $_POST[ "reason_{$reason_key}" ] ) )
            $reason_text = $_POST[ "reason_{$reason_key}" ];

        $this->admin_api->send_feedback( $reason_key, $reason_text );

        wp_send_json_success();
    }

}