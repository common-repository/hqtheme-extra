<?php
defined('ABSPATH') || exit;

use const HQExtra\PLUGIN_NAME;

$categories = HQExtra\Demos\Ui::get_filters();
?>

<div class="hqt-container">
    <div class="wp-filter">
        <ul class="filter-links hqt-filter-category">
            <?php
            $first = 1;
            foreach ($categories as $category_key => $category) {
                echo '<li><a href="#" rel="' . $category_key . '" ' . ($first ? 'class="all current"' : '') . '>' . $category['title'] . '</a></li>';
                $first = 0;
            }
            ?>
        </ul>

        <form class="search-form search-plugins" method="get">
            <input type="hidden" name="tab" value="search">
            <label><span class="screen-reader-text"><?php _ex('Search', 'admin demos filters', 'hqtheme-extra') ?></span>
                <input type="search" name="s" value="<?php echo apply_filters('hqt/demo_import/listing/search_value', '') ?>" class="wp-filter-search" placeholder="<?php _ex('Search', 'admin demos filters', 'hqtheme-extra') ?>..." aria-describedby="live-search-desc">
            </label>
            <input type="submit" id="search-submit" class="button hide-if-js" value="<?php _ex('Search', 'admin demos filters', 'hqtheme-extra') ?>">
        </form>
    </div>
    <div id="hqt-templates-results" class="theme-browser rendered"></div>
    <div class="spinner-wrap">
        <span class="spinner"></span>
    </div>
</div>

<div id="template-details"></div>

<script type="text/template" id="tmpl-hqt-templates-template-preview">
<?php include dirname(__FILE__) . '/templates-template-preview.php'; ?>
</script>

<script type="text/template" id="tmpl-hqt-templates-list">
    <?php include dirname(__FILE__) . '/templates-list.php'; ?>
</script>
