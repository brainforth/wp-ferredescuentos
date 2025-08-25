<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Accordion
 * Description: Display Accordion content
 */
class TB_Accordion_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Accordion', 'themify');
    }

    public static function get_module_icon():string {
        return 'layout-accordion-merged';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1,
            'js' => 1
        );
    }
    /**
     * Render plain content for static content.
     * 
     * @param array $module 
     * @return string
     */
    public static function get_static_content(array $module):string {
        $mod_settings = $module['mod_settings']+array(
            'mod_title_accordion' => '',
            'content_accordion' => array()
        );
        $output = '' !== $mod_settings['mod_title_accordion']?sprintf('<h3>%s</h3>', $mod_settings['mod_title_accordion']):'';

        if ( ! empty( $mod_settings['content_accordion'] ) ) {
			$output .= '<ul>';
            foreach ( $mod_settings['content_accordion'] as $accordion ) {
				$output .= '<li>';				
				if ( ! empty( $accordion['title_accordion'] ) ) {
					$output .= '<h4>' . $accordion['title_accordion'] . '</h4>';
				}

				if ( isset( $accordion['text_accordion'] ) ) {
                    $output .= $accordion['text_accordion'];
                } else if ( ! empty( $accordion['builder_content'] ) && is_array( $accordion['builder_content'] ) ) {
					$output .= ThemifyBuilder_Data_Manager::_get_all_builder_text_content( $accordion['builder_content'] );
				}
				$output .= '</li>';				
            }
			$output .= '</ul>';
        }

        return $output;
    }

    public static function subrow_attributes( $attr ) {
        remove_filter( 'themify_builder_subrow_attributes', [ __CLASS__, 'subrow_attributes' ] );
        $attr['itemprop'] = 'text';
        return $attr;
    }

    public static function get_styling_image_fields() : array {
        return [
            'bg_i' => [ ' .ui.module-accordion .accordion-title', ' .ui.module-accordion>li' ]
        ];
    }

	public static function get_translatable_fields( $module, $classname ) : array {
		$fields = [];
		if ( ! empty( $module['mod_settings']['content_accordion'] ) ) {
			foreach ( $module['mod_settings']['content_accordion'] as $row_index => $acc ) {
				if ( isset( $acc['title_accordion'] ) ) {
					$fields[] = [
						'id' => 'title_accordion-' . $row_index,
						'value' => $acc['title_accordion']
					];
				}

				if ( isset( $acc['builder_content'] ) ) {
					foreach ( $acc['builder_content'] as $subrow ) {
						Themify_Builder_WPML_Integration::recursive_register_row_translatable_fields( $subrow );
					}
				}
			}
		}

		return $fields;
	}

	public static function translate_module( $module_data, $translations ) {
		/* translate titles */
		foreach ( $translations as $item_key => $value ) {
			list( $field, $index ) = explode( '-', $item_key );
			if ( isset( $module_data['mod_settings']['content_accordion'][ $index ][ $field ] ) ) {
				$module_data['mod_settings']['content_accordion'][ $index ][ $field ] = $value;
			}
		}

		if ( ! empty( $module_data['mod_settings']['content_accordion'] ) ) {
			foreach ( $module_data['mod_settings']['content_accordion'] as $row_index => &$acc ) {
				if ( isset( $acc['builder_content'] ) ) {
					foreach ( $acc['builder_content'] as &$subrow ) {
						$subrow = Themify_Builder_WPML_Integration::recursive_translate_fields( $subrow );
					}
				}
			}
		}

		return $module_data;
	}
}
