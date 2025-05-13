<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Bewta_Plugin_Loader {

    public function __construct() {
        $this->load_features();
    }

    private function load_features() {
        $features_dir = BEWTA_PLUGIN_PATH . 'features/';
        foreach ( glob( $features_dir . 'class-*.php' ) as $file ) {
            require_once $file;
        }

        // Manually initialize feature classes
        if ( class_exists('Bewta_Universal_Form_Capture') ) {
            new Bewta_Universal_Form_Capture();
        }

        if ( class_exists('Bewta_Form_Capture_Admin_Settings') ) {
            new Bewta_Form_Capture_Admin_Settings();
        }

        if ( class_exists('Bewta_Form_Capture_Admin_Campaign') ) {
            new Bewta_Form_Capture_Admin_Campaign();
        }
    }
}
