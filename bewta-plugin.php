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

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('bewta-form-css', BEWTA_PLUGIN_URL . 'assets/css/bewta-frontend.css', [], '1.0');
});

add_shortcode('bewta_api_form', function() {
    $form_data = get_option('bewta_api_response_form');

    if (!$form_data) return '<p>No form data available.</p>';

    $output = '<form class="bewta-api-form" method="post">';
    $output .= '<input type="hidden" name="bewta_api_form_submission" value="1">';

    foreach ($form_data['sections'] as $section) {
        $output .= '<fieldset>';
        if (!empty($section['name'])) {
            $output .= '<legend>' . esc_html($section['name']) . '</legend>';
        }

        foreach ($section['fields'] as $field) {
            $field_id = esc_attr($field['fieldKey']);
            $label = esc_html($field['displayName']);
            $type = ($field['fieldType'] === 'email') ? 'email' : 
                    (($field['fieldType'] === 'phone') ? 'tel' : 
                    (($field['fieldType'] === 'text') ? 'text' : 'text'));
            $required = $field['isRequired'] ? 'required' : '';
            $readonly = $field['isReadOnly'] ? 'readonly' : '';
            $placeholder = esc_attr($field['placeholder']);
            $tooltip = esc_attr($field['tooltip']);

            $output .= '<div class="bewta-form-group">';
            $output .= '<label for="' . $field_id . '">' . $label . '</label>';
            $output .= '<input 
                            type="' . $type . '" 
                            name="' . $field_id . '" 
                            id="' . $field_id . '" 
                            placeholder="' . $placeholder . '" 
                            title="' . $tooltip . '" 
                            ' . $required . ' ' . $readonly . '>';
            $output .= '</div>';
        }

        $output .= '</fieldset>';
    }

    $output .= '<div class="bewta-form-actions"><button type="submit">Submit</button></div>';
    $output .= '</form>';

    return $output;
});
