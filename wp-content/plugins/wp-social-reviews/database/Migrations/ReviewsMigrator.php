<?php

namespace WPSocialReviews\Database\Migrations;

class ReviewsMigrator
{
    static $tableName = 'wpsr_reviews';

    public static function migrate()
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,		
				`platform_name` varchar(255),
				`source_id` varchar(255),
				`review_id` varchar(255),
				`category` varchar(255),
				`review_title` varchar(255),
				`reviewer_name` varchar(255),
				`reviewer_url` varchar(255),
				`reviewer_img` TEXT NULL,
				`reviewer_text` LONGTEXT NULL,
				`review_time` timestamp NULL,
				`rating` int(11),
				`review_approved` int(11) DEFAULT 1,
				`recommendation_type` varchar(255),
				`fields` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_platform_name` (`platform_name`),
                INDEX `idx_review_id` (`review_id`),
                INDEX `idx_source_id` (`source_id`),
                INDEX `idx_rating` (`rating`)
            ) $charsetCollate;";
            dbDelta($sql);
        } else {
            static::alterTable($table);
        }
    }

    public static function alterTable($table)
    {
        global $wpdb;
        $existing_columns = $wpdb->get_col("DESC {$table}", 0);
        if(!in_array('category', $existing_columns)) {
            $query = 'ALTER TABLE '.$table.' ADD `category` varchar(255) NULL AFTER `source_id`';
            $wpdb->query($query);
        }

        if(!in_array('review_approved', $existing_columns)) {
            $query = 'ALTER TABLE '.$table.' ADD `review_approved` int(11) DEFAULT 1 AFTER `recommendation_type`';
            $wpdb->query($query);
        }

        if(!in_array('review_id', $existing_columns)) {
            $query = 'ALTER TABLE '.$table.' ADD `review_id` varchar(255) NULL AFTER `source_id`';
            $wpdb->query($query);
        }

        if(!in_array('fields', $existing_columns)) {
            $query = 'ALTER TABLE '.$table.' ADD `fields` LONGTEXT NULL AFTER `recommendation_type`';
            $wpdb->query($query);
        }

        $sql =  "ALTER TABLE $table
        MODIFY COLUMN reviewer_img TEXT NULL";
        $wpdb->query($sql);

        static::addMissingIndexes($table);
    }

    public static function addMissingIndexes($table)
    {
        global $wpdb;

        // Safely escape table name
        $table = esc_sql($table);

        $existing_indexes = $wpdb->get_results("SHOW INDEX FROM `$table`");
        $existing_index_names = [];

        foreach ($existing_indexes as $index) {
            $existing_index_names[] = $index->Key_name;
        }

        $indexes = [
            'idx_platform_name' => 'platform_name',
            'idx_review_id'     => 'review_id',
            'idx_source_id'     => 'source_id',
            'idx_rating'        => 'rating',
        ];

        foreach ($indexes as $index_name => $column_name) {
            if (!in_array($index_name, $existing_index_names)) {
                $sql = $wpdb->prepare(
                    "ALTER TABLE `$table` ADD INDEX `$index_name` (`$column_name`)"
                );
                $wpdb->query($sql);
            }
        }
    }
}
