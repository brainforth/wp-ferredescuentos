<?php
/**
 * Custom functions specific to the skin
 *
 * @package Themify Ultra
 */

/**
 * Load Google web fonts required for the skin
 *
 * @since 1.4.9
 * @return array
 */
function themify_theme_jewelry_google_fonts( $fonts ) {
	if ( 'off' !== _x( 'on', 'Sorts Mill Goudy font: on or off', 'themify' ) ) {
		$fonts['Sorts+Mill+Goudy'] = 'Sorts+Mill+Goudy:400,400i';
	}
	if ( 'off' !== _x( 'on', 'Nunito font: on or off', 'themify' ) ) {
		$fonts['Nunito'] = 'Nunito:300,400,600';
	}
	return $fonts;
}
add_filter( 'themify_google_fonts', 'themify_theme_jewelry_google_fonts' );