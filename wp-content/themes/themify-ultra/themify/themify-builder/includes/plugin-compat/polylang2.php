<?php
/**
 * Builder Plugin Compatibility Code
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

class Themify_Builder_Plugin_Compat_polylang2 {

	static function init() {
		add_filter( 'pll_copy_post_metas', [ __CLASS__, 'pll_copy_post_metas' ], 20, 2 );
	}

	/**
	 * Copy Builder data when translating posts
	 */
	public static function pll_copy_post_metas( $metas, $sync ) {
		if ( ! $sync ) {
			$metas[] = '_themify_builder_settings_json';
		}

		return $metas;
	}
}