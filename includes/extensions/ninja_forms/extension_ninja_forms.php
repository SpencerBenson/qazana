<?php
namespace Builder\Extensions;

class Ninja_Forms extends Base {

	public function get_config() {

        return [
        	'title' => __( 'Ninja Forms Compatibility', 'builder' ),
            'name' => 'ninja_forms',
        	'required' => true,
        	'default_activation' => true,
        ];

	}

    public function __construct() {
        add_action( 'init', [ __CLASS__, 'init' ] );
    }

    public static function init() {

        // Hack for Ninja Forms
        if ( class_exists( '\Ninja_Forms' ) ) {
            add_action( 'builder/preview/enqueue_styles', function() {
                ob_start();

                \NF_Display_Render::localize( 0 );

                ob_clean();

                wp_add_inline_script( 'nf-front-end', 'var nfForms = nfForms || [];' );
            } );
        }

    }

}