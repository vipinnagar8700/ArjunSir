<?php

class OnTheGoSystemsStyles_Class{

    const VERSION_NUMBER = '4.36';

    private static $instance;

    /**
     * Class is singleton
     */
    private function __construct( )
    {
		// Register on wp_loaded:10 because this is instantiated after init
		add_action( 'wp_loaded', array( &$this, 'register_styles' ) );
        // Load in wp-admin
        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
        // Load in front-end
        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
		// Load on demand
		add_action( 'otg_action_otg_enforce_styles', array( &$this, 'enforce_enqueue_styles' ) );
    }
	
	public function register_styles() {
		wp_register_style( 'onthego-admin-styles-icons', ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthegosystems-icons/css/onthegosystems-icons.css', array(), self::VERSION_NUMBER );
		wp_register_style( 'onthego-admin-styles-colors', ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthego-styles/onthego-colors.css', array(), self::VERSION_NUMBER );
		wp_register_style( 'onthego-admin-styles-helper', ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthego-styles/onthego-styles-helper.css', array(), self::VERSION_NUMBER );
		wp_register_style( 'onthego-admin-styles-core', ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthego-styles/onthego-admin-styles.css', array(), self::VERSION_NUMBER );
		wp_register_style( 'onthego-admin-styles-buttons', ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthego-styles/onthego-buttons.css', array(), self::VERSION_NUMBER );
        
        wp_register_style(
            'onthego-admin-styles',
            ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthego-styles/onthego-styles.css',
            array(
                'onthego-admin-styles-icons',
                'onthego-admin-styles-colors',
                'onthego-admin-styles-helper',
                'onthego-admin-styles-core',
                'onthego-admin-styles-buttons',
            ),
            self::VERSION_NUMBER
        );
	}

    public function enqueue_styles()
    {
        if ( 
			is_admin() 
			|| (
                // Load on frontend for Layouts related needs, only if the current usre is logged in.
                // Otherwise, the current user will never reach the Layouts frontend editor.
                defined('WPDDL_VERSION')
                && is_user_logged_in()
            )
		) {
            wp_enqueue_style( 'onthego-admin-styles' );
        }
    }
	
	public function enforce_enqueue_styles() {
		if ( ! wp_style_is( 'onthego-admin-styles' ) ) {
			wp_enqueue_style( 'onthego-admin-styles' );
		}
	}

    public static function getInstance( )
    {
        if (!self::$instance)
        {
            self::$instance = new OnTheGoSystemsStyles_Class();
        }

        return self::$instance;
    }
};
