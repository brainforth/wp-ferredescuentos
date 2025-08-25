<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Copyright
 * Description: Display copyright text
 */
class TB_Copyright_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        add_filter('themify_builder_active_vars',array(__CLASS__,'set_active_vars'));
        return __('Copyright', 'themify');
    }

    public static function get_module_icon():string {
        return '-1';
    }


    public static function set_active_vars(array $arr){
        if(!is_admin()){
            $arr['modules']['copyright']['site_data']=array(
                'site_name'=>get_bloginfo('name'),
                'site_description'=>get_bloginfo('description'),
                'site_url'=>home_url(),
                'year'=>wp_date('Y')
            );
        }
        return $arr;
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'title' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'text' ];
	}
}