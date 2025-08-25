<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Testimonials
 * Description: Display testimonial custom post type
 */
class TB_Testimonial_Slider_Module extends Themify_Builder_Component_Module {

    
    public static function get_module_name():string {
        return __('Testimonials', 'themify');
    }

    public static function get_js_css():array {
        return array(
            'css' => 1,
            'async' => 1
        );
    }

    public static function get_module_icon():string {
        return 'clipboard';
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_testimonial' ];
	}

	public static function get_translatable_repeatable_fields( $module ) {
		return [
			'tab_content_testimonial' => [
				'title_testimonial' => 'LINE',
				'content_testimonial' => 'TEXTAREA',
				'person_picture_testimonial' => 'LINK',
				'person_name_testimonial' => 'LINE',
				'person_position_testimonial' => 'LINE',
				'company_testimonial' => 'LINE',
				'company_website_testimonial' => 'LINE'
			]
		];
	}
}


