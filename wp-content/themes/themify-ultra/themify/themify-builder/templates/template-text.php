<?php
/**
 * Template Text
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-text.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined('ABSPATH') || exit;

$mod_name = $args['mod_name'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'mod_title_text' => '',
    'content_text' => '',
    'text_drop_cap' => '',
    'add_css_text' => '',
    'background_repeat' => '',
    'animation_effect' => ''
);

$container_class = apply_filters('themify_builder_module_classes', array(
    'module',
    'module-' . $mod_name,
    $element_id,
    $fields_args['add_css_text'],
    $fields_args['background_repeat'],
    $fields_args['text_drop_cap'] === 'dropcap' ? 'tb_text_dropcap' : ''
    ), $mod_name, $element_id, $fields_args);

if (!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active === false) {
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', self::parse_animation_effect($fields_args, array(
        'class' => implode(' ', $container_class),
    )), $fields_args, $mod_name, $element_id);

if (Themify_Builder::$frontedit_active === false) {
    $container_props['data-lazy'] = 1;
}
self::sticky_element_props($container_props, $fields_args);
?>
<!-- module text -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php
    $container_props = $container_class = $args = null;
    echo Themify_Builder_Component_Module::get_module_title($fields_args, 'mod_title_text');
    ?>
    <div  class="tb_text_wrap">
        <?php echo apply_filters('themify_builder_module_content', $fields_args['content_text']); ?>
    </div>
</div>
<!-- /module text -->