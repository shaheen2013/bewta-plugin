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

        if (isset($_POST['action']) && $_POST['action'] === 'heartbeat') return;
        if ($this->already_captured) return;

        $this->already_captured = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {

            // 🔄 Normalize data: decode Fluent Form's 'data' field if present
            $raw_data = $_POST;

            if (
                isset($_POST['action']) && $_POST['action'] === 'fluentform_submit'
                && isset($_POST['data'])
                && (!isset($_POST['names']) || !isset($_POST['email']) || !isset($_POST['phoneNumber']))
            ) {
                parse_str($_POST['data'], $fluent_data);
                $raw_data = array_merge($raw_data, $fluent_data);
            }

            $data = [];

            // 🔑 Field name patterns
            $first_name_keys = ['name', 'full_name', 'fullname', 'your-name', 'your_name', 'first_name', 'firstname', 'fname', 'given_name', 'givenName'];
            $email_keys      = ['email', 'email_address', 'your-email', 'your_email', 'user_email', 'emailaddress', 'emailAddress'];
            $phone_keys      = ['phone', 'phone_number', 'phonenumber', 'mobile', 'telephone', 'tel', 'your-phone', 'your_phone', 'contact_number', 'mobile_number'];

            // 🔍 Loop and map fields
            foreach ($raw_data as $key => $value) {
                $lower_key = strtolower($key);

                // Preserve all fields
                if (in_array($key, ['address', 'socialProfiles'])) {
                    $data[$key] = is_array($value) ? $value : [$value];
                } else {
                    $data[$key] = $value;
                }

                // Required field mappings
                if (!isset($data['firstName']) && in_array($lower_key, $first_name_keys)) {
                    $data['firstName'] = is_array($value) ? implode(' ', $value) : $value;
                }

                if (!isset($data['email']) && in_array($lower_key, $email_keys)) {
                    $data['email'] = is_array($value) ? reset($value) : $value;
                }

                if (!isset($data['phoneNumber']) && in_array($lower_key, $phone_keys)) {
                    $data['phoneNumber'] = is_array($value) ? reset($value) : $value;
                }
            }

            // ✅ Handle nested names[first_name] from Fluent Forms
            if (!isset($data['firstName']) && isset($raw_data['names']['first_name'])) {
                $data['firstName'] = $raw_data['names']['first_name'];
            }

            if (!isset($data['lastName']) && isset($raw_data['names']['last_name'])) {
                $data['lastName'] = $raw_data['names']['last_name'];
            }

            // 🧾 Debug log
            error_log("[{$context}] Captured form data: " . print_r($data, true));

            // ✅ Required field check
            if (empty($data['firstName']) || empty($data['email']) || empty($data['phoneNumber'])) {
                error_log("[{$context}] Missing required fields: firstName, email, or phoneNumber.");
                return;
            }

            // ✅ Send to external API
            $apiKey = get_option('bewta_form_capture_api_key');
            if ($apiKey && !empty($data)) {
                $query = 'mutation AddContactWithApiKey($apiKey: String, $data: Mixed) {
                    addContactWithApiKey(apiKey: $apiKey, data: $data) {
                        id
                    }
                }';

                $variables = [
                    'apiKey' => $apiKey,
                    'data'   => $data,
                ];

                $response = wp_remote_post('https://api.bewta.com/graphql', [
                    'headers' => [
                        'Content-Type'  => 'application/json',
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
