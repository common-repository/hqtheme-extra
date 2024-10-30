<?php

namespace HQLib;

use const HQLib\Version;

class License {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Admin 
     */
    private static $_instance = null;

    /**
     * Freemius product id
     * @var type int
     */
    private static $_freemius_product_id = 7293;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Admin
     */
    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        // License Ajax Hooks
        add_action('wp_ajax_hqtheme-license-activate', [$this, 'license_activate']);
        add_action('wp_ajax_hqtheme-license-deactivate', [$this, 'license_deactivate']);
    }

    /**
     * Activate license ajax
     * 
     * @since 1.0.0
     */
    public function license_activate() {
        check_ajax_referer('hq-license', '_ajax_nonce');

        $license_field_name = \HQLib\HQLIB_PREFIX . self::get_license_field_name();

        if (empty($_REQUEST[$license_field_name])) {
            wp_send_json_error(_x('License Key Missing', 'activate license', 'hqtheme-extra'));
        }

        $license_key = sanitize_text_field($_REQUEST[$license_field_name]);

        $url = \HQLib\HQLib::get_templates_api_url() . '/license-activate.php?key=' . $license_key . '&domain=' . \HQLib\License::get_site_domain();
        $data = \HQLib\Helper::get_json($url);

        if (false !== $data && $data->success) {
            if (!$data->item_id) {
                wp_send_json_error(_x('Item ID Missing', 'license deactivate', 'hqtheme-extra'));
            }
            // Store purchase code for populating
            update_option($license_field_name, $license_key);

            wp_send_json_success();
        } else {
            wp_send_json_error(_x('Invalid Purchase Code', 'license deactivate', 'hqtheme-extra'));
        }
    }

    /**
     * Deactivate license ajax
     * 
     * @since 1.0.0
     */
    public function license_deactivate() {
        check_ajax_referer('hq-license', '_ajax_nonce');

        $license_field_name = \HQLib\HQLIB_PREFIX . self::get_license_field_name();

        if (empty($_REQUEST[$license_field_name])) {
            wp_send_json_error(_x('License Key Missing', 'license deactivate', 'hqtheme-extra'));
        }

        $license_key = sanitize_text_field($_REQUEST[$license_field_name]);

        $url = \HQLib\HQLib::get_templates_api_url() . '/license-deactivate.php?key=' . $license_key . '&domain=' . \HQLib\License::get_site_domain();
        $data = \HQLib\Helper::get_json($url);

        if (false !== $data) {
            // Delete purchase code for populating
            delete_option($license_field_name);

            wp_send_json_success();
        } else {
            wp_send_json_error($data->message);
        }
    }

    public static function license_container() {
        $is_activated = self::is_activated();

        $fields = [];
        $license_field = \HQLib\Field::mk('input', self::get_license_field_name(), _x('Purchase Code', 'license page', 'hqtheme-extra'), false)
                ->set_classes('hqt-col-1-1 hqt-col-1-2__lg')
                ->add_attribute('disabled', $is_activated ? true : false)
                ->set_content_before(self::license_status_section());

        //$fields[] = $license_field;
        $fields[] = \HQLib\Field::mk('html', 'license_status', '', false)
                ->set_classes('hqt-col-1-1 hqt-col-1-2__lg')
                ->set_html(self::license_status_section());
        $fields[] = \HQLib\Field::mk('html', 'license_info_box', '', false)
                ->set_classes('hqt-col-1-1 hqt-col-1-2__lg')
                ->set_html(self::license_info_box());

        $container = \HQLib\Options\Container::mk('license', '', false)
                //->disable_title()
                ->add_field($fields)
                ->set_buttons(false);
        /*                ->set_buttons([
          'save' => [
          'type' => 'submit',
          'id' => 'license_activate',
          'class' => 'btn-primary' . ($is_activated ? ' hidden' : ''),
          'name' => 'activate',
          'label' => _x('Use License Key', 'license page', 'hqtheme-extra'),
          ],
          'deactivate' => [
          'type' => 'submit',
          'id' => 'license_deactivate',
          'class' => 'btn-danger' . (!$is_activated ? ' hidden' : ''),
          'name' => 'deactivate',
          'label' => _x('Remove License Key', 'license page', 'hqtheme-extra'),
          ],
          'get-license' => [
          'type' => 'link',
          'link' => THEME_SITE_URL . '/pricing/?utm_source=wp-admin&utm_medium=button&utm_campaign=default&utm_term=hqtheme-extra&utm_content=license-bottom',
          'target' => '_blank',
          'class' => 'btn-border' . ($is_activated ? ' hidden' : ''),
          'label' => _x('Get License', 'license page', 'hqtheme-extra'),
          ],
          ]); */

        $data = [
            '_ajax_nonce' => wp_create_nonce('hq-license'),
            'license_field_name' => $license_field->get_field_name(),
        ];
        wp_enqueue_script('hqlib-license', LIB_URL . 'assets/js/license.js', ['jquery'], VERSION, true);
        wp_localize_script('hqlib-license', 'hqLicenseData', $data);

        return apply_filters('hqt/container/license/init', $container);
    }

    public static function license_status_section() {
        global $mar_fs;
        $is_activated = self::is_activated();
        $vars = array(
            'id' => self::$_freemius_product_id,
        );
        require_once \HQExtra\PLUGIN_PATH . '/inc/freemius/start.php';

        ob_start();

        // phpcs:disable 
        echo \fs_require_template('forms/license-activation.php', $vars);
        echo \fs_require_template('forms/resend-key.php', $vars);
        ?>
        <div class="hqt-row">
            <div class="hqt-col-1-1">
                <?php if ($is_activated) : ?>
                    <p class="text-bg text-success p-2 mt-0 mb-2"><?php _ex('You have a valid license!', 'license status', 'hqtheme-extra'); ?></p>
                    <p class="mt-0 mb-2"><?php _ex('Congratulations! Your license is now activated and you are ready to use all the premium features.', 'license status', 'hqtheme-extra'); ?></p>
                    <div class="mt-0 mb-2">
                        <?php echo sprintf('<a href="%s" class="btn btn-border">%s</a>', $mar_fs->get_account_url(), _x('Manage Account', 'license status', 'hqtheme-extra')); ?>
                    </div>
                <?php else : ?>
                    <h2 class="mt-0 mb-3"><?php _ex('Upgrade your plan', 'license status', 'hqtheme-extra'); ?></h2>
                    <p class="mt-0 mb-2"><?php _ex('Now you are using the free version of Marmot Theme. Upgrade your plan and start using all the amazing premium features.', 'license status', 'hqtheme-extra'); ?></p>
                    <p class="text-bg text-danger p-2 mt-0 mb-2"><?php _ex('You have no active PRO license.', 'license status', 'hqtheme-extra'); ?></p>
                    <div class="mt-0 mb-2">
                        <?php echo sprintf('<a href="%s" class="btn btn-primary">%s</a>', $mar_fs->pricing_url(), _x('Get PRO License', 'license status', 'hqtheme-extra')); ?>
                        <a href="#" class="activate-license-trigger <?php echo $mar_fs->get_unique_affix(); ?> ml-2">
                            <?php _ex('Already have a license key?', 'license status', 'hqtheme-extra'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function license_info_box() {
        ob_start();
        ?>
        <div class="hqt-row">
            <div class="hqt-col-1-1">

                <?php
                if (self::is_activated()) {
                    ?>
                    <h2 class="mt-0 mb-3"><?php _ex('You\'re ready to go!', 'license info', 'hqtheme-extra'); ?></h2>
                    <p class="mt-0 mb-2"><?php _ex('Start building your website like a Pro! Enjoy all the premium features - import awesome templates with a click, create stunning popups, attach custom headers and footers on each page, use advanced widgets for Elementor with <b>Dynamic Tags</b>, create unique WooCommerce checkout flow and more, and more...', 'license info', 'hqtheme-extra'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=marmot#marmot-enhancer-pro')); ?>" class="btn btn-border mt-3"><?php _ex('Start Using Premium Features', 'license info', 'hqtheme-extra'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-ready-sites')); ?>" class="btn btn-danger-border mt-3 ml-2"><?php _ex('Import Demo', 'license info', 'hqtheme-extra'); ?></a>

                    <?php
                } else {
                    // Get license button
                    ?>
                    <h2 class="mt-0 mb-3"><?php _ex('Get premium theme features to build impressive websites.', 'license info', 'hqtheme-extra'); ?></h2>
                    <?php echo self::enhancer_pro_features(); ?>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function enhancer_pro_features() {
        ob_start();
        ?>
        <p class="mt-0 mb-2"><?php _ex('With <b>Marmot  PRO</b> you get access to incredible plugins and features for powerful WordPress designing.', 'admin pro features', 'hqtheme-extra'); ?></p>
        <ul class="hqt-list list-border m-0">
            <li><?php _ex('Access to all PRO demos, features and plugins, including <b>Element Pack</b>, <b>Revolution Slider</b> and <b>Layer Slider</b>', 'admin pro features', 'hqtheme-extra'); ?></li>            
            <li><?php _ex('Attach custom headers and footers on each page, post, product, archive or custom post type', 'admin pro features', 'hqtheme-extra'); ?></li>
            <li><?php echo sprintf(_x('Create stunning popups with <a href="%s" target="_blank">%s</a>', 'admin pro features', 'hqtheme-extra'), THEME_SITE_URL . '/popups-for-elementor/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_content=license-details', 'Popups System for Elementor'); ?></li>
            <li><?php _ex('Use advanced widgets for Elementor', 'admin pro features', 'hqtheme-extra'); ?></li>
            <li><?php echo sprintf(_x('Customize your <a href="%s" target="_blank">%s</a>', 'admin pro features', 'hqtheme-extra'), THEME_SITE_URL . '/woocommerce-integration/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_content=license-details', 'WooCommerce checkout flow'); ?></li>
            <li><?php _ex('Control content visibility with Dynamic conditions', 'admin pro features', 'hqtheme-extra'); ?></li>
            <li><?php _ex('Dynamic Tags give you opportunity to display content from the current page or post, changing dynamically according to the post type it’s on', 'admin pro features', 'hqtheme-extra'); ?></li>            
            <li><?php echo sprintf(_x('and more... <a href="%s" target="_blank">%s</a>', 'admin pro features', 'hqtheme-extra'), THEME_SITE_URL . '/features/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_term=hqtheme-extra&utm_content=learn-more', _x('View all features', 'admin', 'hqtheme-extra')); ?></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    public static function get_user_license() {
        return md5(self::get_site_domain() . (self::is_activated() ? '5' : '25' ));
    }

    public static function is_activated() {
        if (in_array(self::get_site_domain(), ['prodemos-test.hqthemes.net', 'prodemos.hqwebs.net'])) {
            return true;
        }
        global $mar_fs;
        if (isset($mar_fs)) {
            return $mar_fs->can_use_premium_code();
        }
        return false;
    }

    public static function get_site_domain() {
        return $_SERVER['HTTP_HOST'];
    }

    public static function get_license_field_name() {
        return 'license_key';
    }

}
