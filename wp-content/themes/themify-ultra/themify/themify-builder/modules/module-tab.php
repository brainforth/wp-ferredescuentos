<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Tab
 * Description: Display Tab content
 */
class TB_Tab_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Tab', 'themify');
    }

    public static function get_module_icon():string {
        return 'layout-tab';
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
        $mod_settings = $module['mod_settings']+ array(
            'mod_title_tab' => '',
            'tab_content_tab' => array()
        );
        $text ='' !== $mod_settings['mod_title_tab']?sprintf('<h3>%s</h3>', $mod_settings['mod_title_tab']): '';
        if (!empty($mod_settings['tab_content_tab'])) {
            $text .= '<ul>';
            foreach ($mod_settings['tab_content_tab'] as $content) {
				$text .= '<li>';
				if ( ! empty( $content['title_tab'] ) ) {
					$text .= '<h4>' . $content['title_tab'] . '</h4>';
				}
                if ( isset( $content['text_tab'] ) ) {
                    $text .= $content['text_tab'];
                } else if ( ! empty( $content['builder_content'] ) && is_array( $content['builder_content'] ) ) {
					$text .= ThemifyBuilder_Data_Manager::_get_all_builder_text_content( $content['builder_content'] );
				}
				$text .= '</li>';
            }
            $text .= '</ul>';
        }
        return $text;
    }

    public static function get_styling_image_fields() : array {
        return [
            'bg_i' => '.ui .tab-nav li.current'
        ];
    }

	public static function get_translatable_fields( $module, $classname ) : array {
		$fields = [];
		if ( ! empty( $module['mod_settings']['tab_content_tab'] ) ) {
			foreach ( $module['mod_settings']['tab_content_tab'] as $row_index => $tab ) {
				if ( isset( $tab['title_tab'] ) ) {
					$fields[] = [
						'id' => 'title_tab-' . $row_index,
						'value' => $tab['title_tab']
					];
				}

				if ( isset( $tab['builder_content'] ) ) {
					foreach ( $tab['builder_content'] as $subrow ) {
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
			if ( isset( $module_data['mod_settings']['tab_content_tab'][ $index ][ $field ] ) ) {
				$module_data['mod_settings']['tab_content_tab'][ $index ][ $field ] = $value;
			}
		}

		if ( ! empty( $module_data['mod_settings']['tab_content_tab'] ) ) {
			foreach ( $module_data['mod_settings']['tab_content_tab'] as $row_index => &$tab ) {
				if ( isset( $tab['builder_content'] ) ) {
					foreach ( $tab['builder_content'] as &$subrow ) {
						$subrow = Themify_Builder_WPML_Integration::recursive_translate_fields( $subrow );
					}
				}
			}
		}

		return $module_data;
	}
}