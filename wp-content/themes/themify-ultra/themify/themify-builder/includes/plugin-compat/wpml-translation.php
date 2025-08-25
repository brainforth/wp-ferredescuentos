<?php
/**
 * WPML Translation compatibility
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

// WPML string types: 'LINE', 'TEXTAREA', 'VISUAL', 'LINK'

class Themify_Builder_WPML_Integration {

	/* cached wpml $package_data during register_strings */
	private static $package_data;

	/* cached string translations */
	private static $translations;

    static function init() {
		add_filter( 'wpml_page_builder_support_required', [ __CLASS__, 'wpml_page_builder_support_required' ] );
		add_action( 'wpml_page_builder_register_strings', [ __CLASS__, 'wpml_page_builder_register_strings' ], 10, 2 );
		add_action( 'wpml_page_builder_string_translated', [ __CLASS__, 'wpml_page_builder_string_translated' ], 10, 5 );
	}

	/* register Builder package */
	static function wpml_page_builder_support_required( $plugins ) {
		$plugins[] = 'Themify Builder';

		return $plugins;
	}

	static function wpml_page_builder_register_strings( $post, $package_data ) {
		if ( 'Themify Builder' === $package_data['kind'] ) {
			$builder_data = ThemifyBuilder_Data_Manager::get_data( $post->ID );
			self::$package_data = $package_data;
			do_action( 'wpml_start_string_package_registration', $package_data );
			if ( is_array( $builder_data ) ) {
				foreach ( $builder_data as $row ) {
					self::recursive_register_row_translatable_fields( $row );
				}
			}
			do_action( 'wpml_delete_unused_package_strings', $package_data );
		}
	}

	static function wpml_page_builder_string_translated(
		$package_kind,
		$translated_post_id,
		$original_post,
		$string_translations,
		$lang
	) {
		// Make sure the package is for our plugin
		if ( 'Themify Builder' === $package_kind ) {
			$builder_data = ThemifyBuilder_Data_Manager::get_data( $original_post->ID );
			if ( ! empty( $builder_data ) && is_array( $builder_data ) ) {
				self::$translations = self::group_string_translation_by_elementid( $string_translations, $lang );
				foreach ( $builder_data as $index => $row ) {
					$builder_data[ $index ] = self::recursive_translate_fields( $row );
				}
			}
			ThemifyBuilder_Data_Manager::save_data( $builder_data, $translated_post_id );
		}
	}

	private static function group_string_translation_by_elementid( $string_translations, $lang ) {
		$result = [];
		if ( ! empty( $string_translations ) ) {
			foreach ( $string_translations as $key => $value ) {
				if ( isset( $value[ $lang ]['value'] ) ) { /* just being cautios */
					list( $post_id, $element_id, $option_id ) = explode( '/', $key );
					$result[ $element_id ][ $option_id ] = $value[ $lang ]['value'];
				}
			}
		}

		return $result;
	}

	public static function recursive_translate_fields( $row ) {
		if ( ! empty( $row['styling'] ) ) {
			$row['styling'] = self::translate_shared_fields( $row['styling'], $row['element_id'] );
		}

		if ( ! empty( $row['cols'] ) ) {
			foreach ( $row['cols'] as &$col ) {
				if ( ! empty( $col['styling'] ) ) {
					$col['styling'] = self::translate_shared_fields( $col['styling'], $col['element_id'] );
				}

				if ( ! empty( $col['modules'] ) ) {
					foreach ( $col['modules'] as &$mod ) {
						if ( isset( $mod['mod_name'], $mod['element_id'], $mod['mod_settings'] )
							/* modules with nested Builder content are always sent for translation */
							&& ( isset( self::$translations[ $mod['element_id'] ] ) || in_array( $mod['mod_name'], [ 'tab', 'accordion', 'toggle' ], true ) )
						) {
							$module = self::get_module( $mod['mod_name'] );
							if ( $module ) {
								$mod = $module::translate_module( $mod, self::$translations[ $mod['element_id'] ] );
								$mod['mod_settings'] = self::translate_shared_fields( $mod['mod_settings'], $mod['element_id'] );
							}
						}
						$mod = self::recursive_translate_fields( $mod ); // for subrows
					}
				}
			}
		}

		return $row;
	}

	public static function recursive_register_row_translatable_fields( $row ) {
		if ( ! empty( $row['styling'] ) ) {
			self::register_translatable_shared_fields( $row['styling'], $row['element_id'] );
		}
		if ( ! empty( $row['cols'] ) ) {
			foreach ( $row['cols'] as $col ) {
				if ( ! empty( $col['styling'] ) ) {
					self::register_translatable_shared_fields( $col['styling'], $col['element_id'] );
				}

				if ( ! empty( $col['modules'] ) ) {
					foreach ( $col['modules'] as $mod ) {
						if ( isset( $mod['mod_name'] ) ) {
							$module = self::get_module( $mod['mod_name'] );
							if ( $module ) {
								$translatable_fields = $module::get_translatable_fields( $mod, $module );
								self::register( $translatable_fields, $mod['element_id'] );
								if ( ! empty( $mod['mod_settings'] ) ) {
									self::register_translatable_shared_fields( $mod['mod_settings'], $mod['element_id'] );
								}
							}
						}
						self::recursive_register_row_translatable_fields( $mod ); // for subrows
					}
				}
			}
		}
	}

	/**
	 * Register fields that exist in all components and can be translated
	 */
	private static function register_translatable_shared_fields( $component, $component_id ) {
		$fields = [];
		if ( isset( $component['_tooltip'] ) ) {
			$fields[] = [
				'value' => $component['_tooltip'],
				'id' => '_tooltip'
			];
		}
		if ( isset( $component['_link'] ) ) {
			$fields[] = [
				'value' => $component['_link'],
				'id' => '_link'
			];
		}

		if ( ! empty( $fields ) ) {
			self::register( $fields, $component_id );
		}
	}

	/**
	 * Translate shared fields that exist in all components
	 */
	private static function translate_shared_fields( $component, $component_id ) {
		if ( isset( self::$translations[ $component_id ]['_tooltip'] ) ) {
			$component['_tooltip'] = self::$translations[ $component_id ]['_tooltip'];
		}
		if ( isset( self::$translations[ $component_id ]['_link'] ) ) {
			$component['_link'] = self::$translations[ $component_id ]['_link'];
		}

		return $component;
	}

	public static function get_module( $name ) {
		$m = Themify_Builder_Component_Module::load_modules( $name );
		if ( is_object( $m ) ) {
			return get_class( $m );
		} else if ( is_string( $m ) ) {
			return $m;
		}
	}

	private static function register( $translatable_fields, $element_id ) {
		if ( ! empty( $translatable_fields ) ) {
			foreach ( $translatable_fields as $field ) {
				do_action(
					'wpml_register_string',
					$field['value'],
					self::$package_data['post_id'] . '/' . $element_id . '/' . $field['id'],
					self::$package_data,
					isset( $field['title'] ) ? $field['title'] : '',
					isset( $field['type'] ) ? $field['type'] : 'LINE'
				);
			}
		}
	}
}