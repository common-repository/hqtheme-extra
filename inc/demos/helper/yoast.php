<?php

namespace HQExtra\Demos\Helper;

defined('ABSPATH') || exit;

/**
 * Yoast
 *
 * @since 1.0.4
 */
class Yoast {

    public static function reset_indexables() {
        
        if (!defined('WPSEO_VERSION')) {
            return true;
        }
        
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange -- We know what we're doing. Really.
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'yoast_indexable');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'yoast_indexable_hierarchy');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'yoast_migrations');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'yoast_primary_term');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'yoast_prominent_words');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'yoast_seo_links');

        // phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange

        \WPSEO_Options::set('ignore_indexation_warning', false);
        \WPSEO_Options::set('indexation_warning_hide_until', false);
        \WPSEO_Options::set('indexation_started', false);
        \WPSEO_Options::set('indexables_indexation_completed', false);

        // Found in Indexable_Post_Indexation_Action::TRANSIENT_CACHE_KEY.
        \delete_transient('wpseo_total_unindexed_posts');
        // Found in Indexable_Post_Type_Archive_Indexation_Action::TRANSIENT_CACHE_KEY.
        \delete_transient('wpseo_total_unindexed_post_type_archives');
        // Found in Indexable_Term_Indexation_Action::TRANSIENT_CACHE_KEY.
        \delete_transient('wpseo_total_unindexed_terms');

        \delete_option('yoast_migrations_premium');
        return \delete_option('yoast_migrations_free');
    }

}
