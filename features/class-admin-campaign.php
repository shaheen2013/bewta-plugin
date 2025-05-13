<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Bewta_Form_Capture_Admin_Campaign {

    private $form_plugins = [

        // ✅ General & Advanced Form Plugins
        'wpforms-lite/wpforms.php'                  => 'WPForms Lite',
        'wpforms/wpforms.php'                       => 'WPForms Pro',
        'gravityforms/gravityforms.php'             => 'Gravity Forms',
        'formidable/formidable.php'                 => 'Formidable Forms',
        'ninja-forms/ninja-forms.php'               => 'Ninja Forms',
        'contact-form-7/wp-contact-form-7.php'      => 'Contact Form 7',
        'fluentform/fluentform.php'                 => 'Fluent Forms',
        'forminator/forminator.php'                 => 'Forminator',
        'happyforms/happyforms.php'                 => 'HappyForms',
        'everest-forms/everest-forms.php'           => 'Everest Forms',
        'kaliforms/kaliforms.php'                   => 'Kali Forms',
        'weforms/weforms.php'                       => 'weForms',
        'quform/quform.php'                         => 'Quform',
        'arforms-form-builder/arforms-form-builder.php' => 'ARForms',
        'visual-form-builder/visual-form-builder.php' => 'Visual Form Builder',
        'form-maker/form-maker.php'                 => 'Form Maker by 10Web',
        'bitform/bitform.php'                       => 'Bit Form',
        'ws-form/ws-form.php'                       => 'WS Form',
        'caldera-forms/caldera-core.php'            => 'Caldera Forms (legacy)',

        // ✅ Elementor-Specific Form Plugins
        'elementor-pro/elementor-pro.php'           => 'Elementor Pro Forms',
        'metform/metform.php'                       => 'MetForm',
        'cool-formkit-lite/cool-formkit-lite.php'   => 'Cool FormKit Lite',

        // ✅ WooCommerce-Compatible Form Plugins
        'jet-form-builder/jet-form-builder.php'     => 'JetFormBuilder',
        // Formidable, WPForms, Fluent Forms already included above as compatible
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_campaign_page']);
        add_action('admin_post_bewta_save_campaign_settings', [$this, 'save_campaign_settings']);
    }

    public function add_campaign_page() {
        add_submenu_page(
            'bewta-form-capture',
            __('Campaigns', 'bewta-plugin'),
            __('Campaigns', 'bewta-plugin'),
            'manage_options',
            'bewta-form-capture-campaigns',
            [$this, 'render_campaign_page']
        );
    }

    private function get_active_form_plugins() {
        $active_plugins = get_option('active_plugins', []);
        $installed = [];

        foreach ( $this->form_plugins as $slug => $name ) {
            if ( in_array( $slug, $active_plugins ) ) {
                $installed[$slug] = $name;
            }
        }

        return $installed;
    }

    public function render_campaign_page() {
        if ( ! current_user_can('manage_options') ) return;

        $active_plugins = $this->get_active_form_plugins();
        $saved_settings = get_option('bewta_campaign_settings', []);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Campaign Management', 'bewta-plugin'); ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="bewta_save_campaign_settings">
                <?php wp_nonce_field('bewta_campaign_nonce_action', 'bewta_campaign_nonce'); ?>

                <table class="form-table">
                    <tbody>
                    <?php if ( ! empty($active_plugins) ) : ?>
                        <?php foreach ($active_plugins as $slug => $plugin_name) : 
                            $value = isset($saved_settings[$slug]) ? $saved_settings[$slug] : '';
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($plugin_name); ?></th>
                            <td>
                                <input type="text" name="bewta_campaign_settings[<?php echo esc_attr($slug); ?>]" 
                                    value="<?php echo esc_attr($value); ?>" 
                                    style="width: 400px;" />
                                <p class="description">Set value for <?php echo esc_html($plugin_name); ?></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="2"><?php esc_html_e('No supported form plugins are currently active.', 'bewta-plugin'); ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php submit_button('Save Campaign Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function save_campaign_settings() {
        if ( ! current_user_can('manage_options') ) wp_die('Unauthorized user');
        if ( ! isset($_POST['bewta_campaign_nonce']) || ! wp_verify_nonce($_POST['bewta_campaign_nonce'], 'bewta_campaign_nonce_action') ) wp_die('Nonce verification failed');

        $settings = isset($_POST['bewta_campaign_settings']) ? $_POST['bewta_campaign_settings'] : [];
        update_option('bewta_campaign_settings', $settings);

        wp_redirect(admin_url('admin.php?page=bewta-form-capture-campaigns&updated=true'));
        exit;
    }
}
