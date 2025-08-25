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
function themify_theme_contractor_google_fonts( $fonts ){
	/* translators: If there are characters in your language that are not supported by this font, translate this to 'off'. Do not translate into your own language. */
	if ( 'off' !== _x( 'on', 'Roboto: on or off', 'themify' ) ) {
		$fonts['roboto'] = 'Roboto:400,400i,700,700i';
	}
	/* translators: If there are characters in your language that are not supported by this font, translate this to 'off'. Do not translate into your own language. */
	if ( 'off' !== _x( 'on', 'Roboto Condensed: on or off', 'themify' ) ) {
		$fonts['roboto-condensed'] = 'Roboto+Condensed:400,400i,700,700i,900,900i';
	}
	return $fonts;
}
add_filter( 'themify_google_fonts', 'themify_theme_contractor_google_fonts' );