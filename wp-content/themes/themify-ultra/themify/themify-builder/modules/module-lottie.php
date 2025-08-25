<?php
defined( 'ABSPATH' ) || exit;

/**
 * Module Name: Lottie
 * Description: Display Lottie
 */

class TB_Lottie_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string{
        return __('Lottie Animation', 'themify');
    }

    public static function get_module_icon():string{
        return 'lottie';
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'm_t' ];
	}
}