<?php

namespace HQExtra\Admin\Page;

defined('ABSPATH') || exit;

use const Marmot\THEME_VERSION;

class Theme_Templates {

    /**
     * Instance
     * 
     * @since 1.1.0
     * 
     * @var Theme_Templates 
     */
    private static $_instance = null;

    /**
     * Get class instance
     *
     * @since 1.1.0
     *
     * @return Theme_Templates
     */
    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Class constructor
     *
     * @since 1.1.0
     */
    private function __construct() {
        add_action('wp_ajax_hq_setup_template', [$this, 'ajax_hq_setup_template']);
    }

    public function ajax_hq_setup_template() {
        check_ajax_referer('hq-lib', '_ajax_nonce');

        $template_option = isset($_POST['template_option']) ? sanitize_text_field($_POST['template_option']) : '';

        if (empty($template_option)) {
            return wp_send_json_error(sprintf(_x('Missing parameter: %s', 'theme templates', 'hqtheme-extra'), 'template_option'));
        }

        // Set noeltmp
        $set_noeltmp = isset($_POST['set_noeltmp']) ? sanitize_text_field($_POST['set_noeltmp']) : '';
        if (!empty($set_noeltmp)) {
            set_theme_mod($template_option, 'noeltmp');
            wp_send_json_success();
        }

        $sub_template_id = '';
        $remote_sub_template_name = '';
        if ('hq_post_archive_layout' === $template_option) {
            $remote_template_name = 'blog-archive';
            $remote_sub_template_name = 'blog-archive-post';
            $sub_template_option_name = 'post_layout_template';
        } elseif ('hq_product_archive_layout' === $template_option) {
            $remote_template_name = 'woocommerce-archive';
            $remote_sub_template_name = 'woocommerce-archive-post';
            $sub_template_option_name = 'product_layout_template';
        } else {
            $remote_template_name = $template_option;
        }

        if (!empty($remote_sub_template_name)) {
            $file_url = \HQLib\HQLib::get_static_api_url() . '/marmot-templates/' . $remote_sub_template_name . '.json';
            $tmp_file = download_url($file_url);

            if (is_wp_error($tmp_file)) {
                return wp_send_json_error(_x('Template download failed', 'theme templates', 'hqtheme-extra'));
            }

            $importer = new \Elementor\TemplateLibrary\Source_Local();
            $import_result = $importer->import_template('import.json', $tmp_file);

            if (is_wp_error($import_result) || empty($import_result[0])) {
                return wp_send_json_error(_x('Template error', 'theme templates', 'hqtheme-extra'));
            }

            $sub_template_id = $import_result[0]['template_id'];
        }

        $file_url = \HQLib\HQLib::get_static_api_url() . '/marmot-templates/' . $remote_template_name . '.json';
        $tmp_file = download_url($file_url);

        if (is_wp_error($tmp_file)) {
            return wp_send_json_error(_x('Template download failed', 'theme templates', 'hqtheme-extra'));
        }

        $importer = new \Elementor\TemplateLibrary\Source_Local();
        $import_result = $importer->import_template('import.json', $tmp_file);

        if (is_wp_error($import_result) || empty($import_result[0])) {
            return wp_send_json_error(_x('Template error', 'theme templates', 'hqtheme-extra'));
        }

        $template_id = $import_result[0]['template_id'];

        if (!empty($sub_template_id)) {
            $data = get_post_meta($template_id, '_elementor_data', true);
            if (!empty($data)) {
                // Update sub template id
                $pattern = '/"' . $sub_template_option_name . '":"(\d+)"/i';
                $replacement = '"' . $sub_template_option_name . '":"' . $sub_template_id . '"';
                $data = preg_replace($pattern, $replacement, $data);

                // Update processed meta.
                update_metadata('post', $template_id, '_elementor_data', wp_slash($data));
            }
        }

        if ('hq_post_archive_layout' === $template_option) {
            set_theme_mod($template_option, $template_id);
            // Set for Blog if not already set
            if (empty(get_theme_mod('hq_blog_home_layout'))) {
                set_theme_mod('hq_blog_home_layout', $template_id);
            }
        } elseif ('hq_product_archive' === $template_option) {
            set_theme_mod($template_option, $template_id);
            // Set for Woo if not already set
            if (empty(get_theme_mod('hq_woocommerce_general_list_layout'))) {
                set_theme_mod('hq_woocommerce_general_list_layout', $template_id);
            }
        } else {
            set_theme_mod($template_option, $template_id);
        }
        wp_send_json_success(['template_id' => $template_id]);
    }

    public function templates() {
        ?>
        <div class="hqt-admin-page">
            <div class="wrap">
                <h1 class="hqt-invisible"></h1>
                <div class="hqt-logo-wrap">
                    <a href="https://marmot.hqwebs.net/?utm_source=wp-admin&utm_medium=logo&utm_campaign=default&utm_content=theme-options-top" target="_blank">
                        <img src="<?php echo MARMOT_THEME_URL; ?>/assets/images/admin/logo-marmot.png">
                    </a>
                </div>
                <p class="mt-0">Version <?php echo THEME_VERSION; ?></p>
                <ul class="marmot-tabs-nav" data-sticky>
                    <li><a href="#hqt-blog-main"><?php _ex('Main Templates ', 'theme templates', 'hqtheme-extra'); ?></a></li>
                    <li><a href="#hqt-blog-templates"><?php _ex('Blog Templates', 'theme templates', 'hqtheme-extra'); ?></a></li>
                    <?php if (\HQLib\is_plugin_active('woocommerce/woocommerce.php')) { ?>
                        <li><a href="#hqt-woocommerce-templates"><?php _ex('WooCommerce Shop Templates', 'theme templates', 'hqtheme-extra'); ?></a></li>
                    <?php } ?>
                    <li><a href="#hqt-custom-post-types-templates"><?php _ex('Custom Post Types Templates', 'theme templates', 'hqtheme-extra'); ?></a></li>
                </ul>
                <div class="hqt-container">
                    <h2 class="mt-0"><?php _ex('Marmot Theme - Templates', 'theme templates', 'hqtheme-extra'); ?></h2>
                    <p><?php echo esc_html(_x('Marmot theme works with Elementor templates.', 'theme templates', 'hqtheme-extra')) ?></p>
                    <p><?php echo esc_html(_x('On this page you can check if templates are set. If not you can import and setup template with single click.', 'theme templates', 'hqtheme-extra')) ?></p>
                    <p><?php echo esc_html(_x('If template is set you can customize it.', 'theme templates', 'hqtheme-extra')) ?></p>
                    <p>
                        <?php echo esc_html(_x('More about Marmot templates system you can find in our documentation.', 'theme templates', 'hqtheme-extra')) ?><br>
                        <a href="https://marmot.hqwebs.net/documentation/how-to-edit-header-template/" target="_blank" class="btn mt-1"><?php echo esc_html(_x('Documentation', 'theme templates', 'hqtheme-extra')) ?></a></p>
                </div>
                <?php
                $recommended_plugins = Theme_Setup_Wizzard::instance()->get_recommended_plugins();
                $required_plugins = [];
                foreach ($recommended_plugins as $recommended_plugin_slug => $recommended_plugin) {
                    if (!defined($recommended_plugin['constant']) && $recommended_plugin['required']) {
                        $required_plugins[$recommended_plugin_slug] = $recommended_plugin;
                    }
                }
                if (count($required_plugins)) {
                    ?>
                    <div class="hqt-container" style="border-left: solid 4px #d63638;">
                        <h3><?php echo esc_html(_x('Marmot templates require some slugins', 'theme templates', 'hqtheme-extra')) ?></h3>
                        <div class="hqt-row mb-4">
                            <?php
                            foreach ($required_plugins as $required_plugin_slug => $required_plugin) {
                                ?>
                                <div class="hqt-col-1-2">
                                    <div class="hqt-box-shadow p-3 mb-2" >
                                        <h3 class="m-0"><?php echo esc_html($required_plugin['name']); ?></h3>
                                        <p class="my-2"><?php echo esc_html($required_plugin['description']); ?></p>
                                        <?php
                                        $install_url = '';
                                        if (!\HQLib\is_plugin_installed($required_plugin['init'])) {
                                            $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $required_plugin_slug), 'install-plugin_' . $required_plugin_slug);
                                        }
                                        $activate_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $required_plugin['init'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $required_plugin['init']);
                                        ?>
                                        <a href="#" 
                                           data-hqt-btn="install-activate-plugin"
                                           data-action-label="replace"
                                           data-plugin-name="<?php echo esc_attr($required_plugin['name']) ?>" 
                                           data-install-url="<?php echo esc_attr($install_url); ?>" 
                                           data-activate-url="<?php echo esc_attr($activate_url); ?>" 
                                           data-callback="refresh-page"
                                           class="btn btn-primary"
                                           ><?php echo esc_html($required_plugin['name']) ?>
                                        </a>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="hqt-container" id="hqt-blog-main">
                        <h2 class="mt-0"><?php _ex('Main Templates ', 'theme templates', 'hqtheme-extra'); ?></h2>
                        <div class="hqt-row my-3">
                            <?php
                            // Global templates
                            $this->template('hq_header_elementor_template', _x('Header', 'theme templates', 'hqtheme-extra'), 'header', _x('Website main header. In PRO verision you can use different headers by page, post, archives and taxonomies.', 'theme templates', 'hqtheme-extra'), false);
                            $this->template('hq_footer_elementor_template', _x('Footer', 'theme templates', 'hqtheme-extra'), 'footer', _x('Website main footer. In PRO verision you can use different footers by page, post, archives and taxonomies.', 'theme templates', 'hqtheme-extra'), false);
                            $this->template('hq_404_elementor_template', _x('404 - not found', 'theme templates', 'hqtheme-extra'), 'page', _x('Website visitors will see this template if they land on non-existing page.', 'theme templates', 'hqtheme-extra'));
                            ?>
                        </div>
                    </div>
                    <div class="hqt-container" id="hqt-blog-templates">
                        <h2 class="mt-0"><?php _ex('Blog Templates', 'theme templates', 'hqtheme-extra'); ?></h2>
                        <div class="hqt-row my-3">
                            <?php
                            // Blog templates
                            $this->template('hq_post_archive_layout', _x('Blog Home/Archive', 'theme templates', 'hqtheme-extra'), 'archive', sprintf(_x('List articles on blog homepage and blog taxonomy pages. %1$sView blog homepage%2$s. Find out how to control blog templates %3$shere%4$s.', 'theme templates', 'hqtheme-extra'),
                                            '<a href="' . esc_attr(get_post_type_archive_link('post')) . '" target="_blank">',
                                            '</a>',
                                            '<a href="https://marmot.hqwebs.net/documentation/category/blog/" target="_blank">',
                                            '</a>'));
                            $recent_post = wp_get_recent_posts(['numberposts' => '1', 'post_type' => 'post']);
                            $this->template('hq_post_single_standart_layout', _x('Single Post', 'theme templates', 'hqtheme-extra'), 'single', sprintf(_x('Single post template. %1$sView single post%2$s', 'theme templates', 'hqtheme-extra'),
                                            '<a href="' . esc_attr(empty($recent_post[0]) ? '' : get_permalink($recent_post[0]['ID'])) . '" target="_blank">',
                                            '</a>'));
                            ?>
                        </div>
                    </div>
                    <?php
                    $woocommerce_file = 'woocommerce/woocommerce.php';
                    $woocommerce_slug = 'woocommerce';
                    if (\HQLib\is_plugin_active('woocommerce/woocommerce.php')) {
                        ?>
                        <div class="hqt-container" id="hqt-woocommerce-templates">
                            <h2 class="mt-0"><?php _ex('WooCommerce Shop Templates', 'theme templates', 'hqtheme-extra'); ?></h2>
                            <div class="hqt-row my-3">
                                <?php
                                // WooCommerce templates
                                $this->template('hq_product_archive_layout', _x('Shop Home/Archive', 'theme templates', 'hqtheme-extra'), 'archive', sprintf(_x('List products on shop page and shop taxonomy pages. %1$sView shop page%2$s. Find out how to control shop templates %3$shere%4$s.', 'theme templates', 'hqtheme-extra'),
                                                '<a href="' . esc_attr(get_permalink(wc_get_page_id('shop'))) . '" target="_blank">',
                                                '</a>',
                                                '<a href="https://marmot.hqwebs.net/documentation/category/shop/" target="_blank">',
                                                '</a>'));
                                $recent_post = wp_get_recent_posts(['numberposts' => '1', 'post_type' => 'product']);
                                $this->template('hq_product_single_layout', _x('Single Product', 'theme templates', 'hqtheme-extra'), 'single', sprintf(_x('Single product template. %1$sView product page%2$s', 'theme templates', 'hqtheme-extra'),
                                            '<a href="' . esc_attr(empty($recent_post[0]) ? '' : get_permalink($recent_post[0]['ID'])) . '" target="_blank">',
                                            '</a>'));
                                ?>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="hqt-container" id="hqt-woocommerce-templates">
                            <h2 class="mt-0"><?php _ex('WooCommerce Shop Templates', 'theme templates', 'hqtheme-extra'); ?></h2>
                            <p>
                                <?php _ex('After activating WooCommerce plugin templates setup will be available', 'theme templates', 'hqtheme-extra'); ?>
                            </p>
                            <p>
                                <?php
                                $install_url = '';
                                if (!\HQLib\is_plugin_installed($woocommerce_file)) {
                                    $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $woocommerce_slug), 'install-plugin_' . $woocommerce_slug);
                                }
                                $activate_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $woocommerce_file . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $woocommerce_file);
                                ?>
                                <a href="#" 
                                   data-hqt-btn="install-activate-plugin"
                                   data-action-label="replace"
                                   data-plugin-name="WooCommerce" 
                                   data-install-url="<?php echo esc_attr($install_url); ?>" 
                                   data-activate-url="<?php echo esc_attr($activate_url); ?>" 
                                   data-callback="refresh-page"
                                   class="btn btn-primary"
                                   >WooCommerce
                                </a>
                            </p>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="hqt-container" id="hqt-custom-post-types-templates">
                        <h2 class="mt-0"><?php _ex('Custom Post Types Templates', 'theme templates', 'hqtheme-extra'); ?></h2>
                        <p>
                            <?php _ex('Custom Post Types are more specific. That is why default templates are not available.', 'theme templates', 'hqtheme-extra'); ?>
                        </p>
                        <p>
                            <?php
                            echo sprintf(_x('Learn more about how to create custom post type and control templates %1$shere%2$s.', 'theme templates', 'hqtheme-extra'),
                                    '<a target="_blank" href="https://marmot.hqwebs.net/documentation/category/custom-post-types/">',
                                    '</a>'
                            );
                            ?>
                        </p>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    protected function template($option, $name, $type, $description = '', $disable = true) {
        $template_mod = get_theme_mod($option);
        ?>
        <div class="hqt-col-1-2__sm hqt-col-1-4__lg">

            <?php
            switch ($option) {
                case 'hq_header_elementor_template':
                    $how_to_link = '/documentation/how-to-create-and-attach-header/';
                    break;
                case 'hq_footer_elementor_template':
                    $how_to_link = '/documentation/how-to-create-and-attach-footer/';
                    break;
                case 'hq_404_elementor_template':
                    $how_to_link = '/documentation/how-to-create-page-404/';
                    break;
                case 'hq_post_archive_layout':
                    $how_to_link = '/documentation/how-to-create-blog-archive/';
                    break;
                case 'hq_post_single_standart_layout':
                    $how_to_link = '/documentation/how-to-create-single-post/';
                    break;
                case 'hq_product_archive_layout':
                    $how_to_link = '/documentation/how-to-create-archive-product-template/';
                    break;
                case 'hq_product_single_layout':
                    $how_to_link = '/documentation/how-to-create-single-product-page/';
                    break;

                default:
                    break;
            }
            if (empty($template_mod)) {
                ?>
                <div class="border-rad-10 hqt-box-shadow mt-1 mb-4 mx-0 py-3 px-2" style="border-left: solid 4px #f40c3c;">
                    <h3><?php echo esc_html(sprintf(_x('%s template is NOT set.', 'theme templates', 'hqtheme-extra'), $name)); ?></h3>
                    <?php
                    echo $description;
                    ?>
                    <ul>
                        <li><a href="#"
                               data-hqt-btn="setup-template"
                               data-hqt-template-option="<?php echo $option; ?>"
                               data-hqt-template-name="<?php echo $name; ?>"
                               class="btn btn-sm btn-primary"><?php echo esc_html(sprintf(_x('Create and setup default %s template now.', 'theme templates', 'hqtheme-extra'), $name)); ?></a></li>
                            <?php if (!$disable) { ?>
                            <li><a href="#"
                                   data-hqt-btn="disable-template"
                                   data-hqt-template-option="<?php echo $option; ?>"
                                   data-hqt-template-name="<?php echo $name; ?>"
                                   class="btn btn-sm btn-danger"><?php echo esc_html(sprintf(_x('I do not need %s.', 'theme templates', 'hqtheme-extra'), $name)); ?></a></li>
                            <?php } ?>
                        <li><?php echo sprintf(_x('Learn how to create and attach %1$s template %2$shere%3$s.', 'theme templates', 'hqtheme-extra'), $name, '<a target="_blank" href="' . \HQLib\THEME_SITE_URL . $how_to_link . '">', '</a>'); ?></li>
                    </ul>
                </div>

                <?php
            }
            ?>
            <div class="border-rad-10 hqt-box-shadow mt-1 mb-4 mx-0 py-3 px-2<?php echo empty($template_mod) ? ' hidden' : ''; ?>" style="border-left: solid 4px #5cc75f;">
                <h3><?php echo sprintf(_x('%s template is set.', 'theme templates', 'hqtheme-extra'), $name); ?></h3>
                <?php
                echo $description;
                ?>
                <ul>
                    <?php if ('noeltmp' !== $template_mod) { ?>
                        <li><a href="<?php echo esc_attr(empty($template_mod) ? '#' : admin_url('post.php?post=' . $template_mod . '&action=elementor')) ?>" 
                               target="_blank" 
                               data-hqt-btn="setup-template-edit-template"
                               class="btn btn-sm"><?php echo esc_html(sprintf(_x('Edit %s template', 'theme templates', 'hqtheme-extra'), $name)); ?></a></li>
                        <?php } ?>
                    <li><a href="<?php echo esc_attr(admin_url('edit.php?post_type=elementor_library&tabs_group=library&elementor_library_type=' . $type)) ?>"
                           target="_blank" 
                           class="btn btn-sm"><?php echo esc_html(sprintf(_x('Create new %s template', 'theme templates', 'hqtheme-extra'), $name)); ?></a></li>
                    <li><a href="<?php echo esc_attr(admin_url('customize.php?autofocus[control]=' . $option)) ?>"
                           target="_blank" 
                           class="btn btn-sm"><?php echo esc_html(sprintf(_x('Attach other template for %s', 'theme templates', 'hqtheme-extra'), $name)); ?></a></li>
                    <li><?php echo sprintf(_x('Learn how to create and attach %1$s template %2$shere%3$s.', 'theme templates', 'hqtheme-extra'), $name, '<a target="_blank" href="' . \HQLib\THEME_SITE_URL . $how_to_link . '">', '</a>'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

}
