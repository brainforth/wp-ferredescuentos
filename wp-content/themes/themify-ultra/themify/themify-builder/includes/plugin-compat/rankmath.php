<?php
/**
 * Builder Plugin Compatibility Code
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

class Themify_Builder_Plugin_Compat_RankMath {

    static function init() {
        if(is_admin()){
            add_filter( 'rank_math/video/content', [ __CLASS__, 'scan_builder_content' ], 10, 2 );
            add_filter( 'rank_math/links/content', [ __CLASS__, 'scan_builder_content' ], 10, 2 );
            add_filter( 'rank_math/metabox/values', [ __CLASS__, 'has_toc_filter' ] );
        }
        add_filter( 'rank_math/sitemap/content_before_parse_html_images', array( __CLASS__, 'sitemap' ), 10, 2 );
    }

    /**
     * Themify Builder has built-in Table of Content support, let RankMath know
     */
    public static function has_toc_filter(array $values ):array {
        $values['assessor']['hasTOCPlugin'] = true;

        return $values;
    }

    /**
     * Fix the image counter in Rank Math site map.
     *
     * Append a plain text version of Builder output, before Rank Math
     * searches for images in the post content.
     */
    public static function sitemap(string $content,int $post_id ):string {
        $builder_data = ThemifyBuilder_Data_Manager::get_data( $post_id );
        $plain_text = ThemifyBuilder_Data_Manager::_get_all_builder_text_content( $builder_data );
        $plain_text = do_shortcode( $plain_text ); // render shortcodes that might be in the Themify_Builder_Component_Module::get_plain_text()

        return $content . $plain_text;
    }

    /**
     * Enable RankMath to scan Builder's contents
     */
    public static function scan_builder_content(string $content, int $object_id ):string {
        return $content . ' ' . Themify_Builder::render( $object_id );
    }
}