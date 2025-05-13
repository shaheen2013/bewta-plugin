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
        'piotnet-addons-for-elementor/piotnet-addons-for-elementor.php' => 'Piotnet for Elementor',

        // ✅ WooCommerce-Compatible Form Plugins
        'jet-form-builder/jet-form-builder.php'     => 'JetFormBuilder',
        'tripetto/tripetto.php'                     => 'Tripetto Forms',
        'advanced-forms/advanced-forms.php'         => 'Advanced Forms',
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
        foreach ($this->form_plugins as $slug => $name) {
            if (in_array($slug, $active_plugins)) {
                $installed[$slug] = $name;
            }
        }
        return $installed;
    }

    private function get_plugin_forms($plugin_slug) {
        $forms = [];
        switch ($plugin_slug) {
            case 'fluentform/fluentform.php':
                $forms = class_exists('\FluentForm\App\Models\Form') ? \FluentForm\App\Models\Form::all() : [];
                break;
            case 'wpforms-lite/wpforms.php':
            case 'wpforms/wpforms.php':
                $forms = function_exists('wpforms') ? wpforms()->form->get() : [];
                break;
            case 'gravityforms/gravityforms.php':
                $forms = class_exists('GFAPI') ? GFAPI::get_forms() : [];
                break;
            case 'ninja-forms/ninja-forms.php':
                $forms = function_exists('Ninja_Forms') ? Ninja_Forms()->form()->get_forms() : [];
                break;
            case 'formidable/formidable.php':
                $forms = class_exists('FrmForm') ? FrmForm::get_published_forms() : [];
                break;
            case 'contact-form-7/wp-contact-form-7.php':
                $forms = class_exists('WPCF7_ContactForm') ? WPCF7_ContactForm::find() : [];
                break;
            case 'everest-forms/everest-forms.php':
                $forms = function_exists('evf_get_forms') ? evf_get_forms() : [];
                break;
            case 'forminator/forminator.php':
                $forms = class_exists('Forminator_API') ? Forminator_API::get_forms() : [];
                break;
            // Add more plugins as needed
        }
        return $forms;
    }

    public function render_campaign_page() {
        if (!current_user_can('manage_options')) return;

        $active_plugins = $this->get_active_form_plugins();
        $saved_settings = get_option('bewta_campaign_settings', []);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Campaign Management', 'bewta-plugin'); ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="bewta_save_campaign_settings">
                <?php wp_nonce_field('bewta_campaign_nonce_action', 'bewta_campaign_nonce'); ?>

                <?php if (!empty($active_plugins)) : ?>
                    <?php foreach ($active_plugins as $slug => $plugin_name) : ?>
                        <h2><?php echo esc_html($plugin_name); ?></h2>
                        <?php
                        $forms = $this->get_plugin_forms($slug);
                        if (!empty($forms)) :
                            $forms_data = [];
                            foreach ($forms as $form) {
                                if ($form instanceof WPCF7_ContactForm) {
                                    $forms_data[$form->id()] = $form->title();
                                } elseif ($form instanceof WP_Post) {
                                    $forms_data[$form->ID] = $form->post_title;
                                } elseif (is_object($form)) {
                                    if (isset($form->id) && isset($form->title)) {
                                        $forms_data[$form->id] = $form->title;
                                    }
                                } elseif (is_array($form)) {
                                    $id = $form['id'] ?? '';
                                    $title = $form['title'] ?? $form['post_title'] ?? '';
                                    if ($id && $title) {
                                        $forms_data[$id] = $title;
                                    }
                                }
                            }
                        ?>
                            <select class="bewta-form-dropdown" data-plugin="<?php echo esc_attr($slug); ?>">
                                <option value=""><?php esc_html_e('Select a form', 'bewta-plugin'); ?></option>
                                <?php foreach ($forms_data as $form_id => $form_title) : ?>
                                    <option value="<?php echo esc_attr($form_id); ?>" <?php selected(isset($saved_settings[$slug][$form_id]), true); ?>>
                                        <?php echo esc_html($form_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if (!empty($saved_settings[$slug])) : ?>
                                <?php foreach ($saved_settings[$slug] as $saved_form_id => $saved_value) : ?>
                                    <div class="bewta-form-setting" data-plugin="<?php echo esc_attr($slug); ?>" data-form-id="<?php echo esc_attr($saved_form_id); ?>">
                                        <label><?php echo esc_html($forms_data[$saved_form_id] ?? $saved_form_id); ?></label>
                                        <input type="text" name="bewta_campaign_settings[<?php echo esc_attr($slug); ?>][<?php echo esc_attr($saved_form_id); ?>]" value="<?php echo esc_attr($saved_value); ?>" style="width: 400px;" />
                                        <button type="button" class="button bewta-remove-setting"><?php esc_html_e('Remove', 'bewta-plugin'); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        <?php else : ?>
                            <p><?php esc_html_e('No forms found for this plugin.', 'bewta-plugin'); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p><?php esc_html_e('No supported form plugins are currently active.', 'bewta-plugin'); ?></p>
                <?php endif; ?>

                <?php submit_button('Save Campaign Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function save_campaign_settings() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized user');
        if (!isset($_POST['bewta_campaign_nonce']) || !wp_verify_nonce($_POST['bewta_campaign_nonce'], 'bewta_campaign_nonce_action')) wp_die('Nonce verification failed');

        $settings = isset($_POST['bewta_campaign_settings']) ? $_POST['bewta_campaign_settings'] : [];
        update_option('bewta_campaign_settings', $settings);

        wp_redirect(admin_url('admin.php?page=bewta-form-capture-campaigns&updated=true'));
        exit;
    }
}
