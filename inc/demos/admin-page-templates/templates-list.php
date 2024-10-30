<?php
defined('ABSPATH') || exit;
?>
<# if ( Object.keys(data).length ) { #>
<# for ( let key in data ) { #>

<div class="theme template-item" tabindex="0" aria-describedby=""
     data-id="{{{ data[ key ].id }}}"
     data-title="{{{ data[ key ].title }}}"
     data-demo-url="{{{ data[ key ].demo_url }}}"
     data-pro="{{{ data[ key ].pro }}}"
     data-preview-url="{{{ data[ key ].preview_url }}}">

    <div class="inner hqt-preview-demo">
        <span class="site-preview" data-href="{{ data[ key ].preview }}?TB_iframe=true&width=600&height=550" data-title="{{ data[ key ].title }}">
            <div class="theme-screenshot" style="background-image: url('{{ data[ key ].preview_url }}');"></div>
        </span>
        <# if ( data[ key ].pro ) { #>
        <span class="site-type type-pro">PRO</span>
        <# } #>
        <div class="theme-id-container">
            <div class="hqt-demo-metadata">
                <h3 class="hqt-demo-name"> {{{ data[ key ].title }}} </h3>
                <p class="hqt-demo-category">{{{ data[ key ].categories.join(', ') }}}</p>
            </div>
            <div class="hqt-demo-actions">
                <button class="button-primary button"><?php echo esc_html_x('Preview', 'admin demos listing', 'hqtheme-extra'); ?></button>
            </div>
        </div>
    </div>
</div>
<# } #>
<# } else { #>
<p class="no-themes" style="display:block;">
    <?php _ex('No Demos found, Try a different search.', 'admin demos listing', 'hqtheme-extra'); ?>
    <span class="description d-block my-2" style="display:block;"><?php _ex('Don\'t see a site that you would like to import?', 'admin demos listing', 'hqtheme-extra'); ?></span>
    <span class="d-block">
        <?php printf('<a href="%1$s" class="btn btn-primary" target="_blank">' . _x('Please suggest us!', 'admin demos listing', 'hqtheme-extra') . '</a>', esc_url('https://marmot.hqwebs.net/suggest-a-demo/?utm_source=wp-admin&utm_medium=button&utm_campaign=default&utm_content=import-listing')); ?>
    </span>
</p>
<# } #>