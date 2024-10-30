<?php

namespace HQExtra\Admin;

defined('ABSPATH') || exit;

class Menu {

    /**
     * Plugin Instance
     * @var Menu 
     */
    private static $_instance = null;

    /**
     * Get class instance
     *
     * @since 1.0.6
     *
     * @return Menu
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 201);
    }

    public function admin_menu() {

        if (!defined('\Marmot\THEME_SLUG')) {
            return;
        }

        // Init license container
        add_action('admin_init', [\HQLib\License::instance(), 'license_container']);

        // Main menu item
        add_menu_page(
                esc_html__('Dashboard', 'hqtheme-extra'),
                'Marmot Theme',
                'manage_options',
                'marmot',
                [\Marmot\Admin::instance(), 'dashboard'],
                $this->hq_icon(),
                62
        );

        // Dashboard
        add_submenu_page(
                'marmot',
                __('Dashboard', 'hqtheme-extra'),
                esc_html__('Dashboard', 'hqtheme-extra'),
                'manage_options',
                'marmot',
                [\Marmot\Admin::instance(), 'dashboard']
        );

        // Setup Wizzard
        add_submenu_page(
                'marmot',
                __('Marmot Setup Wizzard', 'hqtheme-extra'),
                esc_html__('Setup Wizard', 'hqtheme-extra'),
                'manage_options',
                \Marmot\THEME_SLUG . '-theme-setup',
                [Page\Theme_Setup_Wizzard::instance(), 'theme_setup']
        );

        // Ready sites
        add_submenu_page(
                'marmot',
                '',
                esc_html__('Ready Sites', 'hqtheme-extra'),
                'manage_options',
                \Marmot\THEME_SLUG . '-ready-sites',
                [Page\Ready_Sites::instance(), 'ready_sites']
        );
        
        // Theme Templates
        add_submenu_page(
                'marmot',
                __('Marmot Theme Templates', 'hqtheme-extra'),
                esc_html__('Theme Templates', 'hqtheme-extra'),
                'manage_options',
                \Marmot\THEME_SLUG . '-theme-templates',
                [Page\Theme_Templates::instance(), 'templates']
        );

        // Theme Options
        add_submenu_page(
                'marmot',
                '',
                esc_html__('Theme Options', 'hqtheme-extra'),
                'manage_options',
                \Marmot\THEME_SLUG . '-theme-options',
                [Page\Theme_Options::instance(), 'theme_options']
        );

        // License
        add_submenu_page(
                'marmot',
                '',
                esc_html__('License', 'hqtheme-extra'),
                'manage_options',
                \Marmot\THEME_SLUG . '-license',
                [Page\License::instance(), 'license']
        );

        // Plugins
        add_submenu_page(
                'marmot',
                '',
                esc_html__('Plugins', 'hqtheme-extra'),
                'manage_options',
                \Marmot\THEME_SLUG . '-manage-plugins',
                [Page\Manage_Plugins::instance(), 'manage_plugins']
        );
    }

    public function hq_icon() {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2tpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQ4IDc5LjE2NDAzNiwgMjAxOS8wOC8xMy0wMTowNjo1NyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDoxNmI3ZjMyMy0yNWM0LTY3NDktODA0NC0wMzcwZjFlNTQ2YmEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6Q0FFM0Y5NjYzMzI2MTFFQjg0NUVBRDA4MTA4QjJEQjQiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6Q0FFM0Y5NjUzMzI2MTFFQjg0NUVBRDA4MTA4QjJEQjQiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIDIxLjAgKFdpbmRvd3MpIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RjVGMUI4NUYyRTZGMTFFQjk2MzJFNjRBQkE2QzI5NzgiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RjVGMUI4NjAyRTZGMTFFQjk2MzJFNjRBQkE2QzI5NzgiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz7JlAk9AAAC+ElEQVR42pyUzUsVYRTGn/PO3PHzXjW/iEysKPADEzXQFiYSgW2CdkLQpqBN1EJa9TcUQVCLCEIJFwXSxoQoRKJcZBGSuikkUyK0vNePq3dmTs/MvWa20JsvnDvvvPPOb845z/NeSZ6qPY5c9744fj0cH+J4QI4PRDLzcI33wTyikEj6GSIexFbA5pxXzqch/lVbfRkU19SoAYShtkA2NqCL84DLF3K5FgNMRZQ/NjdxTSS9WfzMnHtEW8WWQRspqYHhwwDm8IXZL1AkYZ2/CDlcR3gcOjMGnXrBD3BPSQZuWVB+ULaDK2y4ZlktFIrDDZMTMA11sG/cglRUQQ7VY3Po9Ch07BGw+An+5zeEOkC+A/VYuiHMF6iPFVlraoyjwIrqzASsliY4j18B60msd1fC6rkM+9o9/Dv8vh7o6ACknDflhRDL3exlQlYbm+NYmotKSR5yRz4AsRgzHYcu/4IUxSDHWtOQodssfRzWlb70/dsH0Oe9EHcJqMhnlgHQT7CHLGd+Ac7dJyEsGFLbHPR525CaFmBjlSQ3FMe0XQLqz0IfnqGAk+xtDttIQVdiB+OmpTaa+3IYexn6lOW/HgCq8gNLJYzGf8LqOr032NQQvBHCiuktl6J4EljSglRX/x/JS0HfP4PXfxGB39TkQTw39C1tzNqrDuwMWFqAN9wPrPwAEnPQr+/o149AEZMpK2VvKYTDrtPPtmnrgNV5cufSvn+De/M6UEhx9nGhJAJTSs/kMbt1+pAwZcmBH22751xI3mlI1RFIA02emAXKiiE5Hk3MM77hITx34UnTsIfG6u7YvWf5BZD6dmZKz3l8OWXSIrjB1YTZhWsMY45mJ4g50QVNIgPKANyt0OBDDJOtsKa9C7K/Ghpfy2RltuCZeRBZA6W8Eqalk2Uv/AXYDg19yL0FWfuvjPZaSWUUDfyn4TVMKwhL8wIgTUQzZnMyVpNUN8I/hE0Ild38Y07DvQB4gXGHEd2VmNI/aoYA2QJT3jjXen8LMABh0jHiWzGgqAAAAABJRU5ErkJggg==';
    }

}
