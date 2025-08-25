<?php
/**
 * Template Testimonial Grid
 *
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-testimonial-grid.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$mod_name=$args['mod_name'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'layout_slider' => '',
    'img_h_slider' => '',
    'img_w_slider' => '',
    'image_size_slider' => '',
    'css_slider' => '',
    'animation_effect' => '',
    'grid_layout_testimonial'=>'list-post',
    'masonry'=>''
);
$fields_args['type_testimonial']='grid';

$container_class =  apply_filters('themify_builder_module_classes', array(
    'module tf_clearfix', 
    'module-' . $mod_name, 
    $element_id,
    $fields_args['css_slider'],
    $fields_args['layout_slider']
), $mod_name, $element_id, $fields_args);

if(!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active===false){
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', self::parse_animation_effect($fields_args,array(
    'id' => $element_id,
    'class' => implode(' ',$container_class)
    )), $fields_args, $mod_name, $element_id);
$fields_args['margin'] = '';
if(Themify_Builder::$frontedit_active===false){
        $container_props['data-lazy']=1;
}
$masonry = 'enable' === $fields_args['masonry'] && $fields_args['grid_layout_testimonial']!=='list-post';
$class=array();
if($masonry===true){
    $class[]='masonry';
}
$class=apply_filters( 'themify_loops_wrapper_class', $class,'testominal',$fields_args['grid_layout_testimonial'], 'builder', $fields_args, $mod_name);
self::sticky_element_props($container_props, $fields_args);
?>
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php echo Themify_Builder_Component_Module::get_module_title($fields_args,'mod_title_slider');?>
    <div class="themify_builder_testimonial loops-wrapper builder-posts-wrap <?php echo implode(' ',$class); ?> tf_clear"<?php if($masonry===true && Themify_Builder::$frontedit_active===false):?> data-lazy="1"<?php endif;?>>
        <?php $container_props=$container_class=null;
        self::retrieve_template('template-' . $mod_name . '-content.php', array(
        'module_ID' => $element_id,
        'mod_name' => $mod_name,
        'settings' => $fields_args
        ), __DIR__);
        ?>
    </div>
</div>
