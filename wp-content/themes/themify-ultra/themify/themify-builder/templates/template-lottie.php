<?php
/**
 * Template lottie
 *
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-lottie.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$mod_name=$args['mod_name'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'm_t' => '',
    'loop'=>1,
    'actions' => array(),
    'css' => '',
    'animation_effect' => '',
);
$container_class =  apply_filters('themify_builder_module_classes', array(
    'module',
    'module-' . $mod_name,
    $element_id,
    $fields_args['css']
), $mod_name, $element_id, $fields_args);

if(!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active===false){
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', self::parse_animation_effect($fields_args,array(
    'class' => implode(' ',$container_class),
)), $fields_args, $mod_name, $element_id);
if (Themify_Builder::$frontedit_active === false) {
    $container_props['data-lazy'] = 1;
}
self::sticky_element_props($container_props, $fields_args);
?>
<!-- module lottie -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php $container_props=$container_class=$args=null;
    echo Themify_Builder_Component_Module::get_module_title($fields_args,'m_t');
    $json=array('actions'=>$fields_args['actions']);
    if(!empty($fields_args['loop'])){
        $json['loop']=1;
    }
    unset($fields_args);
    ?>
    <tf-lottie data-lazy="1" class="tf_w tf_lazy">
        <template><?php echo json_encode($json);unset($json);?></template>
    </tf-lottie>
</div>
<!-- /module lottie -->