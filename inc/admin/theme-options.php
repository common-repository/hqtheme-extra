<?php

namespace HQExtra\Admin;

defined('ABSPATH') || exit;

class Theme_Options {

    /**
     * Plugin Instance 
     */
    private static $_instance = null;

    /**
     * Theme options config data.
     * No need to insert all the options, just those with requirements.
     * @var array
     */
    public static $theme_options = [
        'theme_customizable_mode' => [
            'requires' => [
                'elementor' => [
                    'type' => 'plugin',
                    'plugin_file' => 'elementor/elementor.php',
                    'plugin_name' => 'elementor',
                    'label' => 'Elementor',
                    'link' => 'https://wordpress.org/plugins/elementor/',
                ],
            ]
        ],
    ];

    /**
     * Get class instance
     * @since 1.0.6
     * @return Theme_Options
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('init', [$this, 'add_extra_image_sizes']);

        if (is_admin()) {
            add_action('admin_init', [$this, 'theme_options']);
        }
    }

    /**
     * Add extra image sizes
     */
    public function add_extra_image_sizes() {
        $addional_options = \HQLib\hq_get_option('theme_addional_options');
        if (!empty($addional_options['extra_image_sizes'])) {
            foreach ($addional_options['extra_image_sizes'] as $image_size) {
                if ($image_size['alias'] && $image_size['width'] && $image_size['height']) {
                    $image_crop = 'on' == $image_size['crop'] ? true : false;
                    if ($image_crop && !empty($image_size['crop_location'])) {
                        $image_crop = explode('_', $image_size['crop_location']);
                    }
                    add_image_size($image_size['alias'], $image_size['width'], $image_size['height'], $image_crop);
                }
            }
        }
    }

    /**
     * Generate Theme Options Tabs
     */
    public function theme_options() {
        $tabs = apply_filters('hqt/theme/options/tabs', [
            $this->tab_general_options(),
            $this->tab_theme_modules(),
            $this->tab_additional_options()
        ]);

        $theme_options_tabs = \HQLib\Options\Tabs::mk('hq-theme-options');
        //->set_layout('vertical')
        foreach ($tabs as $tab) {
            $theme_options_tabs->add_tab($tab);
        }
    }

    /**
     * Create General Options Tab
     * @return \HQLib\Options\Container
     */
    private function tab_general_options() {
        // TODO make it dynamic when more options are in place
        $fields = [];
        $customizable_mode_requires_check = \HQLib\Helper::field_requires_check($this->get_option_config('theme_customizable_mode'));
        $fields[] = \HQLib\Field::mk('checkbox', 'theme_customizable_mode', _x('Enable Full Customizable Mode?', 'admin', 'marmot'))
                ->set_classes('align-items-center hqt-col-1-1 hqt-col-2-3__sm hqt-col-1-3__md')
                ->set_description('Enable full customizations mode. Edit hedear, footer and content area with Elementor page bulder. This mode requires "HQTheme Extra" and "Elementor" plugins. Both are free.')
                ->set_default_value($customizable_mode_requires_check->success ? 'on' : 'off')
                ->set_option_value($customizable_mode_requires_check->success ? 'on' : 'off')
                ->set_args(['switch' => true])
                ->add_attribute('disabled', !$customizable_mode_requires_check->success)
                ->set_content_after(!$customizable_mode_requires_check->success ? '<div class="mt-3 border-top-dotted">' . $customizable_mode_requires_check->html . '</div>' : false);

        $container = \HQLib\Options\Container::mk('theme_general_options', _x('General', 'admin', 'marmot'))
                //->disable_title()
                ->set_storage('theme_mods')
                ->add_field($fields);

        return $container;
    }

    /**
     * Create Theme Modules and Features Tab
     * @return \HQLib\Options\Container
     */
    private function tab_theme_modules() {
        // Features Control Tab
        $container = \HQLib\Options\Container::mk('theme_modules', _x('Features & Modules', 'Theme Options', 'hqtheme-extra'))->set_is_grouped(true);

        if (!\HQLib\License::is_activated()) {
            $container->add_button('get-license', [
                'type' => 'link',
                'link' => \HQLib\THEME_SITE_URL . '/pricing/?utm_source=wp-admin&utm_medium=button&utm_campaign=default&utm_term=marmot-enhancer-pro&utm_content=modules-container',
                'target' => '_blank',
                'class' => 'btn-border',
                'label' => _x('Get License', 'admin theme options', 'hqtheme-extra'),
            ]);
        }

        foreach (\HQExtra\Modules\Modules_Control::$modules as $module_key => $module_config) {
            $module_requires_check = \HQLib\Helper::field_requires_check($module_config);
            $contentAfter = false;
            if (!$module_requires_check->success) {
                $contentAfter = '<div class="mt-2 border-top-dotted">' . $module_requires_check->html . '</div>';
            }

            $description = sprintf(
                    '<div class="mt-2 d-flex justify-content-justify">'
                    . '<div>%s</div>'
                    . '<div class="ml-2 min-width-max-content">' . (!empty($module_config['link']) ? '<a href="%s">%s</a>' : '') . '</div>'
                    . '</div>',
                    _x($module_config['description'], 'Theme modules', 'hqtheme-extra'),
                    !empty($module_config['link']) ? $module_config['link'] : '',
                    _x('Learn more', 'Theme modules', 'hqtheme-extra')
            );

            if ('pro' == $module_config['type']) {
                // Display simple html info box with no switch control
                $container->add_field(
                        \HQLib\Field::mk('html', $module_key)
                                ->set_description($description)
                                ->set_classes('hqt-field-border-box hqt-col-1-2__md hqt-col-1-3__lg hqt-col-1-4__xl')
                                ->set_html(sprintf('<span class="flex">%1$s</span>', __($module_config['name'], 'Theme modules', 'hqtheme-extra')))
                                ->set_args(['badge' => 'pro'])
                                ->set_content_after($contentAfter)
                );
            } else {
                // Display switch control box
                $container->add_field(
                        \HQLib\Field::mk('checkbox', $module_key, _x($module_config['name'], 'Theme modules', 'hqtheme-extra'))
                                ->set_classes('hqt-field-border-box hqt-col-1-2__md hqt-col-1-3__lg hqt-col-1-4__xl')
                                ->set_description($description)
                                ->set_default_value($module_requires_check->success ? $module_config['default_activation'] : 'off')
                                ->set_option_value($module_requires_check->success ? 'on' : 'off')
                                ->set_args(['switch' => true])
                                ->add_attribute('disabled', !$module_requires_check->success)
                                ->set_content_after($contentAfter)
                );
            }
        }

        return $container;
    }

    /**
     * Create Additional Options Tab
     * @return \HQLib\Options\Container
     */
    private function tab_additional_options() {
        $repeater_fields = [];
        $repeater_fields[] = \HQLib\Field::mk('input', 'alias')
                ->set_classes('hqt-col-1-1 hqt-col-1-3__sm hqt-col-1-4__lg hqt-col-1-5__xl')
                ->disable_label()
                ->set_input_addon(['prepend' => _x('Alias', 'admin', 'marmot')]);
        $repeater_fields[] = \HQLib\Field::mk('input', 'width')
                ->set_classes('hqt-col-1-1 hqt-col-1-3__sm hqt-col-1-4__lg hqt-col-1-5__xl')
                ->disable_label()
                ->set_input_type('number')
                ->set_input_addon(['prepend' => _x('Width', 'admin', 'marmot'), 'append' => 'px']);
        $repeater_fields[] = \HQLib\Field::mk('input', 'height')
                ->set_classes('hqt-col-1-1 hqt-col-1-3__sm hqt-col-1-4__lg hqt-col-1-5__xl')
                ->disable_label()
                ->set_input_type('number')
                ->set_input_addon(['prepend' => _x('Height', 'admin', 'marmot'), 'append' => 'px']);
        $repeater_fields[] = \HQLib\Field::mk('checkbox', 'crop', _x('Image Hard Crop', 'admin', 'marmot'))
                ->set_classes('align-items-center hqt-col-1-1 hqt-col-1-2__sm hqt-col-1-4__lg hqt-col-1-5__xl')
                ->set_args(['switch' => true]);
        $repeater_fields[] = \HQLib\Field::mk('select', 'crop_location')
                ->set_classes('hqt-col-1-1 hqt-col-1-2__sm hqt-col-1-2__lg hqt-col-1-5__xl')
                ->disable_label()
                ->set_input_addon(['prepend' => _x('Crop Location', 'admin', 'marmot')])
                ->set_options([
                    'left_top' => _x('Top Left', 'admin', 'marmot'),
                    'center_top' => _x('Top Center', 'admin', 'marmot'),
                    'right_top' => _x('Top Right', 'admin', 'marmot'),
                    'left_center' => _x('Center Left', 'admin', 'marmot'),
                    'center_center' => _x('Center Center', 'admin', 'marmot'),
                    'right_center' => _x('Center Right', 'admin', 'marmot'),
                    'left_bottom' => _x('Bottom Left', 'admin', 'marmot'),
                    'center_bottom' => _x('Bottom Center', 'admin', 'marmot'),
                    'right_bottom' => _x('Bottom Right', 'admin', 'marmot'),
                ])
                ->set_default_value('center_center')
                ->set_conditions([['field' => 'crop', 'value' => 'on']]);

        $repeater = \HQLib\Options\Repeater::mk('image_sizes')->add_field($repeater_fields);

        $container = \HQLib\Options\Container::mk('theme_addional_options', _x('Additional Options', 'admin', 'marmot'))
                //->disable_title()
                ->set_is_grouped(true);

        $container->start_fieldset('extra_image_sizes');
        $container->add_field(\HQLib\Field::mk('repeater', 'extra_image_sizes', _x('Extra Image Sizes', 'admin', 'marmot'))->set_repeater($repeater));
        $container->end_fieldset();

        return $container;
    }

    /**
     * Get option config
     * @param string $option
     * @return array|boolean
     */
    public function get_option_config($option) {
        if ($option && isset(self::$theme_options[$option])) {
            return self::$theme_options[$option];
        }
        return false;
    }

}
