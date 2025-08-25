<?php

namespace WPSocialReviews\Database\Migrations;

class ImageOptimizationMigrator
{
    static $tableName = 'wpsr_optimize_images';

    public static function migrate()
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `platform` varchar(255) null,		
                `user_id` varchar(255),
                `user_name` varchar(255),
                `json_data`	LONGTEXT NULL,
                `fields` LONGTEXT NULL,
                `media_id` varchar(1000),
                `sizes`	varchar(1000),
                `aspect_ratio` DECIMAL (4, 2) DEFAULT 0 NOT NULL,
                `images_resized` tinyint(1),
                `last_requested` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_platform` (`platform`)
            ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $created = dbDelta($sql);
            update_option('wpsr_optimize_images_table_status', true, 'no');
            return $created;
        } else {
            static::alterTable($table);
        }

        return false;
    }

    public static function alterTable($table) 
    {
        static::addMissingIndexes($table);
    }

    public static function addMissingIndexes($table)
    {
        global $wpdb;

        // Escape table name
        $table = esc_sql($table);

        // Get existing indexes
        $existing_indexes = $wpdb->get_results("SHOW INDEX FROM `$table`");
        $existing_index_names = [];

        foreach ($existing_indexes as $index) {
            $existing_index_names[] = $index->Key_name;
        }

        // Desired indexes
        $indexes = [
            'idx_user_id' => 'user_id',
            'idx_platform' => 'platform',
        ];

        // Add missing indexes
        foreach ($indexes as $index_name => $column_name) {
            if (!in_array($index_name, $existing_index_names)) {
                $sql = "ALTER TABLE `$table` ADD INDEX `$index_name` (`$column_name`)";
                $wpdb->query($sql);
            }
        }
    }
}