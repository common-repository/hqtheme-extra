<?php
defined('ABSPATH') || exit;
?>
<div class="theme-install-overlay wp-full-overlay expanded">
    <div class="wp-full-overlay-sidebar">
        <div class="wp-full-overlay-header"
             data-gid="{{{data.g_id}}}">
            <button class="close-full-overlay"><span class="screen-reader-text"><?php echo esc_html_x('Close', 'admin demo preview', 'hqtheme-extra') ?></span></button>
            <button class="previous-theme"><span class="screen-reader-text"><?php echo esc_html_x('Previous', 'admin demo preview', 'hqtheme-extra') ?></span></button>
            <button class="next-theme"><span class="screen-reader-text"><?php echo esc_html_x('Next', 'admin demo preview', 'hqtheme-extra') ?></span></button>
        </div>
        <div class="wp-full-overlay-sidebar-content">
            <div class="install-theme-info">

                <span class="site-type type-{{{data.pro ? 'pro' : 'free'}}}">
                    <# if ( data.pro ) { #>
                    <?php echo esc_html_x('PRO', 'admin demos badge', 'hqtheme-extra') ?>
                    <# } else { #>
                    <?php echo esc_html_x('FREE', 'admin demos badge', 'hqtheme-extra') ?>
                    <# } #>
                </span>
                <h3 class="theme-name">{{{data.title}}}</h3>

                <# if ( data.preview_url ) { #>
                <div class="theme-screenshot-wrap">
                    <img class="theme-screenshot" src="{{{data.preview_url}}}" alt="">
                </div>
                <# } #>

                <div class="theme-details">
                    {{{data.details}}}
                </div>

                <# if ( data.pro && !data.has_active_license ) { #>
                <p><?php _ex('In order to import PRO demo you need to have active license.', 'admin demo preview', 'hqtheme-extra'); ?></p>
                <?php global $mar_fs; ?>
                <a href="<?php echo $mar_fs->pricing_url(); ?>" class="button button-primary"><?php echo esc_html_x('Get License', 'admin demo preview', 'hqtheme-extra') ?></a>
                <# } else { #>
                <p>
                    <?php _ex('WARNING!!!<br> Importing demo over old site is dangerous!<br> Please backup your site before use this tool!<br> Import may delete some or all of your content and settings!', 'admin demo preview', 'hqtheme-extra'); ?>
                </p>

                <div class="hqtheme-import-options">
                    <ul>
                        <li class="hqtheme-install-plugins">
                            <label>
                                <input type="checkbox" name="plugins" checked="checked" class="checkbox">
                                <strong><?php _ex('Install and Activate Required Plugins', 'admin demo preview', 'hqtheme-extra'); ?></strong>
                            </label>
                            <# if ( data.required_plugins ) { #>
                            <ul class="required-plugins-list">
                                <# for ( let plugin_key in data.required_plugins ) { #>
                                <li>{{{data.required_plugins[plugin_key].name}}}</li>
                                <# } #>
                            </ul>
                            <# } #>
                        </li>
                        <li class="hqtheme-import-content" data-requires='<?php echo json_encode(['install-plugins']); ?>'>
                            <label>
                                <input type="checkbox" name="xml" checked="checked" class="checkbox">
                                <strong><?php _ex('Import Content & Templates', 'admin demo preview', 'hqtheme-extra'); ?></strong>
                            </label>
                            <div class="description">
                                <p>
                                    <?php _ex('Selecting this option will import templates, dummy pages, posts, images and menus.', 'admin demo preview', 'hqtheme-extra'); ?>
                                </p>
                            </div>
                        </li>
                        <li class="hqtheme-import-customizer" data-requires='<?php echo json_encode(['install-plugins', 'import-content']); ?>'>
                            <label>
                                <input type="checkbox" name="customizer" checked="checked" class="checkbox">
                                <strong><?php _ex('Import Customizer Settings', 'admin demo preview', 'hqtheme-extra'); ?></strong>
                            </label>
                            <div class="description">
                                <p><?php _ex('Customizer is what gives a design to the website and selecting this option replaces your current design with a new one.', 'admin demo preview', 'hqtheme-extra'); ?></p>
                            </div>
                        </li>
                        <li class="hqtheme-reset-demo">
                            <label>
                                <input type="checkbox" name="reset" class="checkbox">
                                <strong><?php _ex('Delete Previously Imported Demo', 'admin demo preview', 'hqtheme-extra'); ?></strong>
                            </label>
                            <div class="description">
                                <p>
                                    <?php _ex('WARNING: Selecting this option will delete data from your current website. Choose this option only if this is intended.', 'admin demo preview', 'hqtheme-extra'); ?>
                                </p>
                            </div>
                        </li>
                    </ul>

                </div>

                <a class="button button-primary hqt-install-template-btn" href="#" data-import="disabled"><?php echo esc_html_x('Import Site', 'admin demo preview', 'hqtheme-extra') ?></a>
                <# } #>
            </div>
        </div>

        <div class="wp-full-overlay-footer">            

            <button type="button" class="collapse-sidebar button" aria-expanded="true" aria-label="Collapse Sidebar">
                <span class="collapse-sidebar-arrow"></span>
                <span class="collapse-sidebar-label"><?php echo esc_html_x('Collapse', 'admin demo preview', 'hqtheme-extra') ?></span>
            </button>

            <div class="devices-wrapper">
                <div class="devices">
                    <button type="button" class="preview-desktop active" aria-pressed="true" data-device="desktop">
                        <span class="screen-reader-text"><?php _ex('Enter desktop preview mode', 'admin demo preview', 'hqtheme-extra'); ?></span>
                    </button>
                    <button type="button" class="preview-tablet" aria-pressed="false" data-device="tablet">
                        <span class="screen-reader-text"><?php _ex('Enter tablet preview mode', 'admin demo preview', 'hqtheme-extra'); ?></span>
                    </button>
                    <button type="button" class="preview-mobile" aria-pressed="false" data-device="mobile">
                        <span class="screen-reader-text"><?php _ex('Enter mobile preview mode', 'admin demo preview', 'hqtheme-extra'); ?></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
    <div class="wp-full-overlay-main">
        <iframe src="{{{data.demo_url}}}" title="<?php echo esc_attr_x('Preview', 'admin demo preview', 'hqtheme-extra') ?>" style="background: #fff;"></iframe>
        <div id="theme-install-result" style="display: none;">
            <div class="inner">
                <div class="import-body">
                    <h2><?php _ex('We\'re importing.', 'admin demo preview', 'hqtheme-extra'); ?></h2>
                    <p><?php _ex('The process will take some time depending on the size of the website/template and speed of connection.', 'admin demo preview', 'hqtheme-extra'); ?></p>
                    <p><?php _ex('Please be patient and DO NOT CLOSE this browser window until the site is imported completely.', 'admin demo preview', 'hqtheme-extra'); ?></p>

                </div>
                <div class="import-instructions">
                    <h2><?php _ex('We\'re done.', 'admin demo preview', 'hqtheme-extra'); ?></h2>
                    <p><?php _ex('Import is completed.', 'admin demo preview', 'hqtheme-extra'); ?></p>
                </div>
                <div class="import-status">
                    <div class="current-importing-status-wrap">
                        <div class="current-importing-status">
                            <div class="current-importing-status-title"></div>
                            <div class="current-importing-status-description"></div>
                        </div>
                    </div>
                    <div class="import-process-wrap">
                        <progress id="progress-bar" class="progress-animated" max="100" value="0"></progress>
                    </div>
                </div>
                <div class="import-footer">
                    <div class="import-btn-wrap">
                        <a href="<?php echo get_home_url(); ?>" class="button-primary button" target="_blank"><?php _ex('Go To Homepage', 'admin demo preview', 'hqtheme-extra'); ?></a>
                        <a href="#" id="btn-import-close" class="button-primary button hq-popup-close-btn"><?php _ex('Close', 'admin demo preview', 'hqtheme-extra'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>