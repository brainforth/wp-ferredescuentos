<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module Name: Map
 * Description: Display Map
 */

class TB_Map_Module extends Themify_Builder_Component_Module {

    
    public static function get_module_name():string{
        add_filter( 'themify_builder_active_vars', array(__CLASS__, 'check_map_api'));
        return __('Map', 'themify');
    }

    public static function get_module_icon():string{
        return 'map-alt';
    }

    /**
     * Handles Ajax request to check map api
     *
     * @since 4.5.0
     */
    public static function check_map_api($values) {
        $googleAPI = themify_builder_get( 'setting-google_map_key', 'builder_settings_google_map_key' );
        $values['google_api'] =  !empty($googleAPI);
        $url = themify_is_themify_theme() ? admin_url( 'admin.php?page=themify#setting-integration-api' ) : admin_url( 'admin.php?page=themify-builder&tab=builder_settings' );
        if(!$values['google_api']) {
            $values['google_api_err'] = sprintf( __('Please enter the required <a href="%s" target="_blank">Google Maps API key</a>.','themify'), $url );
        }
        $bingAPI = themify_builder_get( 'setting-bing_map_key', 'builder_settings_bing_map_key' );
        $values['bing_api'] =  !empty($bingAPI);
        if(!$values['bing_api']) {
            $values['bing_api_err'] = sprintf( __('Please enter the required <a href="%s" target="_blank">Bing Maps API key</a>.','themify'), $url );
        }
        return $values;
    }


    /**
     * Render plain content
     */
    public static function get_static_content(array $module):string {
        $mod_settings = $module['mod_settings']+array(
            'mod_title_map' => '',
            'address_map' => 'Toronto',
            'zoom_map' => 15
        );
        if (!empty($mod_settings['address_map'])) {
            $mod_settings['address_map'] = preg_replace('/\s+/', ' ', trim($mod_settings['address_map']));
        }
        $text = sprintf('<h3>%s</h3>', $mod_settings['mod_title_map']);
        $text .= sprintf(
            '<iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q=%s&amp;t=m&amp;z=%d&amp;output=embed&amp;iwloc=near"></iframe>', urlencode($mod_settings['address_map']), absint($mod_settings['zoom_map'])
        );
        return $text;
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_map', 'latlong_map' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'address_map', 'info_window_map' ];
	}
}