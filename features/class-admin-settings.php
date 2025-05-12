<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Bewta_Form_Capture_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_menu_page(
            __('Bewta Form Capture', 'bewta-plugin'), // Page title
            __('Bewta Form Capture', 'bewta-plugin'), // Menu title
            'manage_options',                         // Capability
            'bewta-form-capture',                     // Menu slug
            [$this, 'render_settings_page'],          // Callback
            'dashicons-admin-generic',                // Icon (you can customize)
            26                                        // Position (adjust as needed)
        );
    }

    public function register_settings() {
        register_setting('bewta_form_capture_settings_group', 'bewta_form_capture_api_key');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Bewta Form Capture Settings', 'bewta-plugin'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bewta_form_capture_settings_group');
                do_settings_sections('bewta_form_capture_settings_group');
                $api_key = get_option('bewta_form_capture_api_key', '');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('API Key', 'bewta-plugin'); ?></th>
                        <td>
                            <input type="text" name="bewta_form_capture_api_key" value="<?php echo esc_attr($api_key); ?>" style="width: 400px;" />
                            <p class="description"><?php esc_html_e('Enter your API Key for the form capture service.', 'bewta-plugin'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
