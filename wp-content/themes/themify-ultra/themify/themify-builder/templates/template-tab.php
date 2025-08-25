<?php
/**
 * Template Tab
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-tab.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$mod_name=$args['mod_name'];
$builder_id = $args['builder_id'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'mod_title_tab' => '',
    'layout_tab' => 'minimal',
    'style_tab' => '',
    'color_tab' => 'tb_default_color',
    'tab_appearance_tab' => '',
    'tab_content_tab' => array(),
    'css_tab' => '',
    'animation_effect' => '',
    'allow_tab_breakpoint' => '',
    'tab_breakpoint'=>'',
    'hashtag'=>'',
    'autoplay' => '',
    'autoplay_timer' => '10',
    'autoplay_c' => '',
    'fx' => '',
    'timerbar' => '',
    'pauseh' => '',
);

if (!empty($fields_args['tab_appearance_tab'])) {
    $fields_args['tab_appearance_tab'] = self::get_checkbox_data($fields_args['tab_appearance_tab']);
    Themify_Builder_Model::load_appearance_css($fields_args['tab_appearance_tab']);
}
Themify_Builder_Model::load_color_css($fields_args['color_tab']);
if($fields_args['color_tab']==='transparent'){
   Themify_Builder_Model::load_module_self_style($mod_name,$fields_args['color_tab']);
}
if($fields_args['layout_tab']!=='tab-frame'){
   Themify_Builder_Model::load_module_self_style($mod_name,$fields_args['layout_tab']);
}

$container_class = apply_filters('themify_builder_module_classes', array(
    'module ui',
    'module-' . $mod_name,
    $element_id, 
    $fields_args['layout_tab'], 
    $fields_args['tab_appearance_tab'], 
    $fields_args['color_tab'], 
    $fields_args['css_tab']
), $mod_name, $element_id, $fields_args);
if($fields_args['style_tab']!='' && $fields_args['style_tab']!=='default'){
    $container_class[] = $fields_args['style_tab'];
}
if(!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active===false){
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', self::parse_animation_effect($fields_args,array(
    'id' => $element_id,
    'class' => implode(' ', $container_class),
)), $fields_args, $mod_name, $element_id);

if(Themify_Builder::$frontedit_active===false){
    $container_props['data-lazy']=1;
    if('yes'===$fields_args['hashtag']){
        $container_props['data-hashtag']=1;
    }
}
if ( '' !== $fields_args['allow_tab_breakpoint'] && '' !== $fields_args['tab_breakpoint'] ){
        $container_props['data-tab-breakpoint'] = $fields_args['tab_breakpoint'];
}

$timer_bar = false;
if ( $fields_args['autoplay'] === 'y' ) {
    $container_props['data-autoplay'] = $fields_args['autoplay_timer'];
    if ( $fields_args['autoplay_c'] === 'y' ) {
        $container_props['data-autoplay-stop-on-click'] = '1';
    }
    $timer_bar = $fields_args['timerbar'] === 'yes' ? true : false;
    if ( $timer_bar ) {
        $container_props['data-timerbar'] = 1;
    }
    if ( $fields_args['pauseh'] === 'no' ) {
        $container_props['data-disablepause'] = 1;
    }
}
$animated = '';
if ( $fields_args['fx'] !== '' ) {
    $container_props['data-fx'] = $fields_args['fx'];
    $animated = ' wow';
}
self::sticky_element_props($container_props, $fields_args);
?>
<!-- module tab -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php $container_props=$container_class=$args=null; 
    echo Themify_Builder_Component_Module::get_module_title($fields_args,'mod_title_tab');
    ?>
    <div class="tab-nav-current-active tf_hide">
        <span class="tab_burger_icon tf_rel"></span>
        <span class="tb_tab_title"><?php echo isset( $fields_args['tab_content_tab'][0]['title_tab'] ) ? $fields_args['tab_content_tab'][0]['title_tab'] : ''; ?></span>
    </div>
    <ul class="tab-nav tf_clearfix tf_scrollbar">
        <?php foreach ($fields_args['tab_content_tab'] as $k => $tab): ?>
        <li <?php echo 0 === $k ? 'class="current" aria-expanded="true"' : 'aria-expanded="false"'; ?>>
            <a href="#tab-<?php echo $element_id , '-' , $k; ?>"<?php if ( $timer_bar ) : ?> class="tf_rel"<?php endif; ?>>
                <?php if (isset($tab['icon_tab'])) : ?><em><?php echo themify_get_icon($tab['icon_tab']); ?></em><?php endif; ?>
                <?php
                if(isset($tab['title_tab']) && $fields_args['style_tab']!=='icon-only'){
                    echo $tab['title_tab'];
                }
                ?>
                <?php if ( $timer_bar ) : ?><span class="tb_tab_timerbar tf_abs"></span><?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php foreach ($fields_args['tab_content_tab'] as $k => $tab): ?>
        <div data-id="tab-<?php echo $element_id , '-' , $k; ?>" class="tab-content tf_clear<?php echo $animated; ?>" <?php echo $k === 0 ? 'aria-hidden="false"' : 'aria-hidden="true"' ?>>
            <?php 
            if (isset($tab['text_tab']) && $tab['text_tab']!=='') {?>
                <div class="tb_text_wrap">
                    <?php echo apply_filters('themify_builder_module_content', $tab['text_tab']);?>
                </div>
            <?php 
            }
            elseif(isset($tab['builder_content'])){
                foreach($tab['builder_content'] as $subrow){
                    Themify_Builder_Component_Subrow::template($subrow,$builder_id);
                }
            }?>
        </div>
    <?php endforeach; ?>
</div>
<!-- /module tab -->
