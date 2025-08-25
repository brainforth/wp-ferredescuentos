<?php

class Themify_Builder_Component_Base {//deprecated

    public static $disable_inline_edit = false;//deprecated use Themify_Builder_Component_Module

    

    public static function parse_animation_effect($settings, array $attr = array()) {//deprecated use instead Themify_Builder_Component_Module::parse_animation_effect
        return Themify_Builder_Component_Module::parse_animation_effect($settings,$attr);
    }

    
    public static function sticky_element_props(array $props,array $fields_args) {//deprecated use instead Themify_Builder_Component_Module::parse_animation_effect
        return Themify_Builder_Component_Module::sticky_element_props($props, $fields_args);
    }

    /**
     * @deprecated
     */
    public static function get_element_attributes($props) {
        return themify_get_element_attributes($props);
    }

    /**
     * Retrieve builder templates
     * @param $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     * @param bool $echo
     * @return string|VOID
     */
    public static function retrieve_template($template_name, $args = array(), $template_path = '', $default_path = '', $echo = true) {
        return Themify_Builder_Component_Module::retrieve_template($template_name, $args, $template_path, $default_path, $echo);
    }

    /**
     * Get template builder
     * @param $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     */
    public static function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
        Themify_Builder_Component_Module::get_template($template_name, $args, $template_path, $default_path);
    }

    /**
     * Locate a template and return the path for inclusion.
     *
     * This is the load order:
     *
     *      yourtheme       /   $template_path  /   $template_name
     *      $default_path   /   $template_name
     */
    public static function locate_template($template_name, $template_path = '', $default_path = '') {
        return Themify_Builder_Component_Module::locate_template($template_name, $template_path, $default_path);
    } 

    /**
     * Deprecated
     */
    public static function get_paged_query() {
        return Themify_Builder_Component_Module::get_paged_query();
    }

    /**
     * Deprecated
     */
    public static function get_param_value(string $string) {
        return Themify_Builder_Component_Module::get_param_value( $string );
    }

    /**
     * Deprecated
     */
    public static function add_inline_edit_fields($name, $condition = true, $hasEditor = false, $repeat = false, $index = -1, $echo = true) {
        return '';
    }

}
        