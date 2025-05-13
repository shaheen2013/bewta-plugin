<?php
/*
Plugin Name: Bewta Universal Form Data Capture
Plugin URI: https://mediusware.com/
Description: Modular plugin to capture all frontend form submissions on your WordPress site.
Version: 5.4.1
Requires at least: 5.8
Requires PHP: 5.6.20
Author: Mediusware Ltd.
Author URI: https://mediusware.com/
License: GPLv2 or later
Text Domain: bewta-plugin
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BEWTA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BEWTA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once BEWTA_PLUGIN_PATH . 'includes/class-loader.php';

add_action('plugins_loaded', function() {
    new Bewta_Plugin_Loader();
});

add_action('admin_enqueue_scripts', function($hook) {
    // Only load on Bewta plugin pages
    if ( isset($_GET['page']) && strpos($_GET['page'], 'bewta-form-capture') !== false ) {

        // Enqueue WordPress jQuery
        wp_enqueue_script('jquery');

        // Enqueue custom admin JS
        wp_enqueue_script(
            'bewta-admin-js',
            BEWTA_PLUGIN_URL . 'assets/js/bewta-admin.js',
            ['jquery'], // Dependencies
            '1.0.0',
            true // Load in footer
        );

        // Enqueue custom admin CSS
        wp_enqueue_style(
            'bewta-admin-css',
            BEWTA_PLUGIN_URL . 'assets/css/bewta-admin.css',
            [],
            '1.0.0'
        );
    }
});
