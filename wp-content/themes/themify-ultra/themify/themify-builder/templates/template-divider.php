<?php
/**
 * Template Divider
 *
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-divider.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$mod_name=$args['mod_name'];
$element_id = $args['module_ID'];
$fields_args=$args['mod_settings']+array(
    'mod_title_divider' => '',
    'style_divider' => 'solid',
    'stroke_w_divider' => 1,
    'color_divider' => '',
    'top_margin_divider' => '',
    'bottom_margin_divider' => '',
    'css_divider' => '',
    'divider_type' => 'fullwidth',
    'divider_width' => 200,
    'divider_align' => 'left',
    'animation_effect' => ''
);
if ($fields_args['divider_type'] === 'custom') {
    $fields_args['divider_align'] = 'divider-' . $fields_args['divider_align'];
    $divider_type = 'divider-' . $fields_args['divider_type'];
} else {
    $divider_type = $fields_args['divider_align'] = $fields_args['divider_width'] = '';
}

    $styles = array(
        'border-width'  => 'stroke_w_divider',
        'border-color'  => 'color_divider',
        'margin-top'    => 'top_margin_divider',
        'margin-bottom' => 'bottom_margin_divider',
        'width'         => 'divider_width'
    );

    $style = '';

    foreach( $styles as $prop => $val ) {
        if( isset( $fields_args[ $val ] ) && $fields_args[ $val ]!=='') {
            $style .= $prop . ': ';
            $style .= $prop === 'border-color'
                ? Themify_Builder_Stylesheet::get_rgba_color( $fields_args[ $val ] ) . ';'
                : $fields_args[ $val ] . 'px;';
        }
    }

$container_class = apply_filters('themify_builder_module_classes', array(
    'module tf_mw', 
    'module-' . $mod_name, 
    $element_id, 
    $fields_args['style_divider'], 
    $fields_args['css_divider'],
    $divider_type,
    $fields_args['divider_align']
), $mod_name, $element_id, $fields_args);

if(!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active===false){
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', self::parse_animation_effect($fields_args,array(
    'class' => implode(' ', $container_class),
    )), $fields_args, $mod_name, $element_id);

if ($style!=='') {
    $container_props['style'] = $style;
}
if(Themify_Builder::$frontedit_active===false){
    $container_props['data-lazy']=1;
}
self::sticky_element_props($container_props, $fields_args);
?>
<!-- module divider -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php $container_props=$container_class=$args=null;
    echo Themify_Builder_Component_Module::get_module_title($fields_args,'mod_title_divider');
    ?>
</div>
<!-- /module divider -->
