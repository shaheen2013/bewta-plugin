<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Bewta_Universal_Form_Capture {

    private $already_captured = false;

    public function __construct() {
        // Normal frontend forms
        add_action('wp_loaded', [$this, 'catch_global_forms'], 1);
        add_action('template_redirect', [$this, 'capture_template_redirect'], 1);

        // Frontend AJAX forms
        add_action('admin_init', [$this, 'check_for_ajax_form_capture'], 1);

        // Last-chance emergency capture
        $this->override_wp_die();
    }

    // private function capture_form_data($context = 'unknown') 
    // {
    //     if (
    //         is_admin() &&
    //         !(
    //             defined('DOING_AJAX') &&
    //             DOING_AJAX &&
    //             (
    //                 !is_user_logged_in() || 
    //                 ( is_user_logged_in() && !current_user_can('edit_posts') )
    //             )
    //         )
    //     ) {
    //         return;
    //     }

    //     if ( isset($_POST['action']) && $_POST['action'] === 'heartbeat' ) return;
    //     // if ( ! isset($_POST['bewta_api_form_submission']) ) return;

    //     if ( $this->already_captured ) return;
    //     $this->already_captured = true;

    //     if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) ) {
    //         // ✅ Prepare data
    //         $data = [];
    //         foreach ($_POST as $key => $value) {
    //             // if ($key === 'bewta_api_form_submission') continue; // skip marker

    //             // ✅ Make 'address' and 'socialProfiles' always arrays
    //             if (in_array($key, ['address', 'socialProfiles'])) {
    //                 $data[$key] = is_array($value) ? $value : [$value];
    //             } else {
    //                 $data[$key] = $value;
    //             }
    //         }

    //         error_log("[{$context}] Form data: " . print_r($data, true));

    //         // ✅ Send to external API
    //         $api_key = get_option('bewta_form_capture_api_key');
    //         if ($api_key && !empty($data)) {
    //             $query = 'mutation Mutation($data: Mixed) {
    //                 addContactWithApiKey(data: $data) {
    //                     id
    //                 }
    //             }';

    //             $variables = ['data' => $data];

    //             $response = wp_remote_post('https://api.bewta.com/graphql', [
    //                 'headers' => [
    //                     'Content-Type'  => 'application/json',
    //                     'Authorization' => 'Bearer ' . $api_key,
    //                 ],
    //                 'body' => json_encode([
    //                     'query' => $query,
    //                     'variables' => $variables
    //                 ]),
    //                 'timeout' => 30
    //             ]);

    //             if (!is_wp_error($response)) {
    //                 $body = wp_remote_retrieve_body($response);
    //                 $result = json_decode($body, true);

    //                 // error_log($body);

    //                 if (isset($result['data']['addContactWithApiKey'][0]['id'])) {
    //                     error_log("[{$context}] API contact created. ID: " . $result['data']['addContactWithApiKey'][0]['id']);
    //                 } else {
    //                     error_log("[{$context}] API contact creation failed. Response: " . $body);
    //                 }
    //             } else {
    //                 error_log("[{$context}] API request error: " . $response->get_error_message());
    //             }
    //         } else {
    //             error_log("[{$context}] API key missing or no form data.");
    //         }
    //     }
    // }

    private function capture_form_data($context = 'unknown') {
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

        if ( isset($_POST['action']) && $_POST['action'] === 'heartbeat' ) return;
        if ( $this->already_captured ) return;

        $this->already_captured = true;

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) ) {

            $data = [];

            if (isset($_POST['action']) && $_POST['action'] === 'fluentform_submit' && isset($_POST['data'])) {
                // ✅ Fluent Form detected
                parse_str($_POST['data'], $parsed_data); // decode URL-encoded string

                // Extract required fields
                $data['firstName']   = $parsed_data['names']['first_name'] ?? '';
                $data['email']       = $parsed_data['email'] ?? '';
                $data['phoneNumber'] = $parsed_data['phoneNumber'] ?? '';

                // Merge other fields
                foreach ($parsed_data as $key => $value) {
                    if ($key === 'names') {
                        foreach ($value as $sub_key => $sub_val) {
                            if (!isset($data[$sub_key])) {
                                $data[$sub_key] = $sub_val;
                            }
                        }
                    } elseif (!isset($data[$key])) {
                        $data[$key] = $value;
                    }
                }

                error_log("[{$context}] Parsed Fluent Form data: " . print_r($data, true));

            } else {
                // ✅ Fallback: generic form
                foreach ($_POST as $key => $value) {
                    if (in_array($key, ['address', 'socialProfiles'])) {
                        $data[$key] = is_array($value) ? $value : [$value];
                    } else {
                        $data[$key] = $value;
                    }
                }

                error_log("[{$context}] Generic form data: " . print_r($data, true));
            }

            // ✅ Ensure required fields for API
            if (empty($data['firstName']) || empty($data['email']) || empty($data['phoneNumber'])) {
                error_log("[{$context}] Missing required fields: firstName, email, or phoneNumber.");
                return;
            }

            // ✅ Send to external API
            $api_key = get_option('bewta_form_capture_api_key');
            if ($api_key && !empty($data)) {
                $query = 'mutation Mutation($data: Mixed) {
                    addContactWithApiKey(data: $data) {
                        id
                    }
                }';

                $variables = ['data' => $data];

                $response = wp_remote_post('https://dee9-45-120-99-224.ngrok-free.app/graphql', [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $api_key,
                    ],
                    'body' => json_encode([
                        'query' => $query,
                        'variables' => $variables
                    ]),
                    'timeout' => 30
                ]);

                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $result = json_decode($body, true);

                    if (isset($result['data']['addContactWithApiKey'][0]['id'])) {
                        error_log("[{$context}] API contact created. ID: " . $result['data']['addContactWithApiKey'][0]['id']);
                    } else {
                        error_log("[{$context}] API contact creation failed. Response: " . $body);
                    }
                } else {
                    error_log("[{$context}] API request error: " . $response->get_error_message());
                }
            } else {
                error_log("[{$context}] API key missing or no form data.");
            }
        }
    }

    // Hook for normal frontend forms
    public function catch_global_forms() {
        $this->capture_form_data('wp_loaded');
    }
    public function capture_template_redirect() {
        $this->capture_form_data('template_redirect');
    }

    // Hook for frontend AJAX forms
    public function check_for_ajax_form_capture() {
        if (
            defined('DOING_AJAX') &&
            DOING_AJAX &&
            $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            $this->capture_form_data('ajax');
        }
    }

    // Last-chance emergency capture
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
