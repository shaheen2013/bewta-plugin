<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Bewta_Universal_Form_Capture {

    private $already_captured = false;

    public function __construct() {
        add_action('wp_loaded', [$this, 'catch_global_forms'], 1);
        add_action('init', [$this, 'catch_init_forms'], 1);
        add_action('template_redirect', [$this, 'catch_template_redirect_forms'], 1);

        // Frontend AJAX hooks
        add_action('admin_init', [$this, 'check_for_ajax_form_capture'], 1);

        $this->override_wp_die();
    }

    private function capture_form_data($context = 'unknown') {
        // Skip admin requests, but allow frontend AJAX requests
        if (
            is_admin() &&
            !(
                defined('DOING_AJAX') &&
                DOING_AJAX &&
                (
                    !is_user_logged_in() || 
                    ( is_user_logged_in() && !current_user_can('edit_posts') )
                )
            )
        ) {
            return;
        }


        if ( $this->already_captured ) return;
        $this->already_captured = true;

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) ) {
            $form_data = [];
            foreach ($_POST as $key => $value) {
                $form_data[] = [
                    'name'  => $key,
                    'value' => $value
                ];
            }

            // Example action: log to debug log
            error_log("[$context] Form captured:\n" . print_r($form_data, true));
        }
    }

    public function catch_global_forms() {
        $this->capture_form_data('wp_loaded');
    }

    public function catch_init_forms() {
        $this->capture_form_data('init');
    }

    public function catch_template_redirect_forms() {
        $this->capture_form_data('template_redirect');
    }

    public function check_for_ajax_form_capture() {
        if (
            defined('DOING_AJAX') &&
            DOING_AJAX &&
            $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            $this->capture_form_data('ajax');
        }
    }

    private function override_wp_die() {
        $original_wp_die = $GLOBALS['wp_die_handler'] ?? null;

        $GLOBALS['wp_die_handler'] = function($message = '', $title = '', $args = []) use ($original_wp_die) {
            if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) ) {
                $this->capture_form_data('wp_die');
            }

            if ($original_wp_die && is_callable($original_wp_die)) {
                call_user_func($original_wp_die, $message, $title, $args);
            } else {
                _default_wp_die_handler($message, $title, $args);
            }
        };
    }
}
