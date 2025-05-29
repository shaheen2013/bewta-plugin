<?php
if (!defined('ABSPATH')) exit;

class Bewta_Form_Capture_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_bewta_generate_api_shortcode', [$this, 'ajax_generate_api_shortcode']);
    }

    public function add_settings_page() {
        add_menu_page(
            __('Bewta Form Capture', 'bewta-plugin'),
            __('Bewta Form Capture', 'bewta-plugin'),
            'manage_options',
            'bewta-form-capture',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            26
        );
    }

    public function register_settings() {
        register_setting('bewta_form_capture_settings_group', 'bewta_form_capture_api_key');
        register_setting('bewta_form_capture_settings_group', 'bewta_form_capture_mode');
    }

    public function enqueue_admin_scripts($hook) {
        if (isset($_GET['page']) && $_GET['page'] === 'bewta-form-capture') {
            wp_enqueue_script('bewta-admin-js', BEWTA_PLUGIN_URL . 'assets/js/bewta-admin.js', ['jquery'], '1.0', true);
            wp_localize_script('bewta-admin-js', 'bewtaSettings', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bewta_api_shortcode'),
            ]);
        }
    }

    public function render_settings_page() {
        $api_key = get_option('bewta_form_capture_api_key', '');
        $mode = get_option('bewta_form_capture_mode', 'plugin');
        $short_code = get_option('bewta_api_shortcodes', '');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Bewta Form Capture Settings', 'bewta-plugin'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bewta_form_capture_settings_group');
                do_settings_sections('bewta_form_capture_settings_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="bewta_form_capture_api_key" value="<?php echo esc_attr($api_key); ?>" style="width: 400px;" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Form Capture Mode</th>
                        <td>
                            <select name="bewta_form_capture_mode" id="bewta_form_capture_mode">
                                <option value="api" <?php selected($mode, 'api'); ?>>API Integrated Form</option>
                                <option value="plugin" <?php selected($mode, 'plugin'); ?>>Plugin Based Form</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="bewta_api_shortcode_row" style="display: <?php echo ($mode === 'api') ? 'table-row' : 'none'; ?>;">
                        <th scope="row">API Form Shortcode</th>
                        <td>
                            <button type="button" class="button" id="bewta_generate_shortcode">Create Form Shortcode</button>
                            <input type="text" readonly id="bewta_generated_shortcode" style="width:400px; margin-left:10px;" value="[<?php echo esc_attr($short_code); ?>]" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function ajax_generate_api_shortcode() 
    {
        check_ajax_referer('bewta_api_shortcode', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $api_key = get_option('bewta_form_capture_api_key');
        if (empty($api_key)) wp_send_json_error('API key is missing.');

        $query = 'query Query {
            getContactFieldSettingsWithApiKey {
                sections {
                    fields {
                        displayName
                        fieldKey
                        placeholder
                        tooltip
                        fieldType
                        isRequired
                        isReadOnly
                    }
                    name
                }
            }
        }';

        $response = wp_remote_post('https://api.bewta.com/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode(['query' => $query]),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) wp_send_json_error($response->get_error_message());

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['data']['getContactFieldSettingsWithApiKey']['sections'])) {
            wp_send_json_error('Invalid API response.');
        }

        // ✅ Store full response under a single option
        update_option('bewta_api_response_form', $data['data']['getContactFieldSettingsWithApiKey']);
        update_option('bewta_api_shortcodes', 'bewta_api_form');

        // ✅ Return fixed shortcode
        wp_send_json_success('[bewta_api_form]');
    }
}
