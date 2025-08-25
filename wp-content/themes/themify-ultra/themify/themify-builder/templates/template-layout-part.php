<?php
/**
 * Template Part
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-layout-part.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$mod_name=$args['mod_name'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'mod_title_layout_part' => '',
    'selected_layout_part' => '',
    'add_css_layout_part' => ''
);
$container_class = apply_filters('themify_builder_module_classes', array(
    'module', 'module-' . $mod_name, $element_id,$fields_args['add_css_layout_part']
                ), $mod_name, $element_id, $fields_args);

if(!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active===false){
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', array(
    'class' => implode(' ', $container_class),
), $fields_args, $mod_name, $element_id);

self::sticky_element_props($container_props, $fields_args);
?>
<!-- module template_part -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php 
    $container_props=$container_class=$args=null;
    $isLoop = Themify_Builder::$is_loop;
    Themify_Builder::$is_loop = true;
    $layoutPart=$fields_args['selected_layout_part']!==''?do_shortcode('[themify_layout_part slug="' . $fields_args['selected_layout_part'] . '"]'):'';
    Themify_Builder::$is_loop = $isLoop;
    if($layoutPart!==''){
    echo Themify_Builder_Component_Module::get_module_title($fields_args,'mod_title_layout_part'),$layoutPart; 
    }
    ?>
</div>
<!-- /module template_part -->