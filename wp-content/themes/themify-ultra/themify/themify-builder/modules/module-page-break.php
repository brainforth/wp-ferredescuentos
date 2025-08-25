<?php

defined( 'ABSPATH' ) || exit;

/**
 * Module Name: Page break
 * Description: Page breaker and pagination
 */
class TB_Page_Break_Module extends Themify_Builder_Component_Module {
    
        
    public static function get_module_name():string{
        return __('Page Break', 'themify');
    }
        
    public static function get_module_icon():string{
        return '-1';
    }

    public static function get_static_content(array $module):string {
        return ''; // no static content for dynamic content
    }
}
