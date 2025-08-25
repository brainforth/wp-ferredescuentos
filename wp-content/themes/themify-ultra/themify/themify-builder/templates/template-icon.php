<?php
/**
 * Template Icon
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-icon.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined('ABSPATH') || exit;

$mod_name = $args['mod_name'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'mod_title_icon' => '',
    'icon_size' => '',
    'icon_style' => '',
    'icon_arrangement' => 'icon_horizontal',
    'icon_position' => '',
    'content_icon' => array(),
    'animation_effect' => '',
    'css_icon' => ''
);
if ($fields_args['icon_position'] !== '') {
    $fields_args['icon_position'] = str_replace('icon_position_', '', $fields_args['icon_position']);
    $fields_args['icon_position'] = 'tf_text' . $fields_args['icon_position'][0];
}
$container_class = apply_filters('themify_builder_module_classes', array(
    'module',
    'module-' . $mod_name,
    $element_id,
    $fields_args['css_icon'],
    $fields_args['icon_size'],
    $fields_args['icon_style'],
    $fields_args['icon_arrangement'],
    $fields_args['icon_position']
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
<!-- module icon -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php
    $container_props = $container_class = $args = $mod_name = null;
    echo Themify_Builder_Component_Module::get_module_title($fields_args, 'mod_title_icon');

    foreach ($fields_args['content_icon'] as $content):
        if(empty($content)){
            continue;
        }
        $content+=array(
            'label' => '',
            'hide_label' => '',
            'link' => '',
            'icon_type' => 'icon',
            'image' => '',
            'icon' => '',
            'new_window' => false,
            'icon_color_bg' => '', /* deprecated Nov 2021 */
            'bg' => '',
            'c' => '',
            'w_i' => '',
            'h_i' => '',
            'link_options' => '',
            'lightbox_width' => '',
            'lightbox_height' => '',
            'lightbox_width_unit' => 'px',
            'lightbox_height_unit' => 'px'
        );
        $styles = [];
        if ($content['bg'] !== '') {
            $styles[] = 'background-color:' . Themify_Builder_Stylesheet::get_rgba_color($content['bg']);
        }
        if ($content['c'] !== '') {
            $styles[] = 'color:' . Themify_Builder_Stylesheet::get_rgba_color($content['c']);
        }
        $link_target = $content['link_options'] === 'newtab' ? ' rel="noopener" target="_blank"' : '';
        $link_lightbox_class = $content['link_options'] === 'lightbox' ? ' class="lightbox-builder themify_lightbox"' : '';
        $lightbox_data = !empty($content['lightbox_width']) || !empty($content['lightbox_height']) ? sprintf(' data-zoom-config="%s|%s"'
                , $content['lightbox_width'] . $content['lightbox_width_unit']
                , $content['lightbox_height'] . $content['lightbox_height_unit']) : false;
        ?>
        <div class="module-icon-item">
            <?php if ($content['link'] !== ''): ?>
                <a href="<?php echo esc_attr($content['link']) ?>"<?php echo $link_target, $lightbox_data, $link_lightbox_class ?>>
                <?php endif; ?>
                <?php
                if ($content['icon_color_bg'] !== '') {
                    if ($content['icon_color_bg'] === 'default') {
                        $content['icon_color_bg'] = $content['icon_color_bg'] === 'default'?'':' ' . $content['icon_color_bg'];
                    }
                    if ($content['icon'] !== '' || $content['image'] !== '') {
                        /* backward compatibility with old options */
                        Themify_Builder_Model::load_color_css($content['icon']);
                    }
                }
                $icon=$content['icon_type']!=='image'?($content['icon_type']!=='l'?($content['icon']!=='' ? themify_get_icon($content['icon']):'') : themify_get_lottie($content, 'parent')):$content['image'];
                ?>
                <?php if ($icon !== '' && 'image' !== $content['icon_type']): ?>
                    <em class="tf_box<?php echo $content['icon_color_bg'] ?>"<?php if (!empty($styles)){ echo ' style="' . implode(';', $styles) . '"';} ?>><?php echo $icon; ?></em>
                <?php elseif ($icon !== '' && 'image' === $content['icon_type']): ?>
                    <?php
                    echo themify_get_image(array(
                        'src' => $content['image'],
                        'w' => $content['w_i'],
                        'h' => $content['h_i'],
                        'class' => 'tf_box',
                        'alt' => $content['label'],
                        'title' => $content['label'],
                        'attr' => Themify_Builder::$frontedit_active === true ? array('data-w' => 'w_i', 'data-h' => 'h_i', 'data-repeat' => 'content_icon', 'data-name' => 'image', 'data-no-update' => 1) : null
                    ));
                    ?>
                <?php endif;?>
                <?php if ($content['label'] !== '') : ?>
                    <?php if ('hide' !== $content['hide_label']) : ?>
                        <span><?php echo $content['label'] ?></span>
                    <?php else: ?>
                        <span class="screen-reader-text"><?php echo $content['label'] ?></span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($content['link'] !== ''): ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<!-- /module icon -->
