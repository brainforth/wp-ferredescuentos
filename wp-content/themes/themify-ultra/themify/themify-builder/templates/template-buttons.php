<?php
/**
 * Template Buttons
 *
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-button.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined('ABSPATH') || exit;

$mod_name = $args['mod_name'];
$element_id = $args['module_ID'];
$fields_args=$args['mod_settings']+ array(
    'mod_title_button' => '',
    'buttons_size' => '',
    'buttons_shape' => 'normal',
    'buttons_style' => 'solid',
    'fullwidth_button' => '',
    'nofollow_link' => '',
    'download_link' => '',
    'display' => 'buttons-horizontal',
    'content_button' => array(),
    'animation_effect' => '',
    'css_button' => ''
);
/* for old button style args */
if (in_array($fields_args['buttons_style'], array('circle', 'rounded', 'squared'), true)) {
    $fields_args['buttons_shape'] = $fields_args['buttons_style'];
} 
elseif ($fields_args['buttons_style'] === 'outline') {
    Themify_Builder_Model::load_module_self_style($mod_name, 'outline');
}
/* End of old button style args */

$container_class = apply_filters('themify_builder_module_classes', array(
    'module',
    'module-' . $mod_name,
    $element_id,
    $fields_args['display'],
    $fields_args['buttons_style'],
    $fields_args['css_button']
    ), $mod_name, $element_id, $fields_args);
if ($fields_args['buttons_size'] !== 'normal') {
    $container_class[] = $fields_args['buttons_size'];
}
if ($fields_args['buttons_shape'] !== 'normal') {
    $container_class[] = $fields_args['buttons_shape'];
    if ($fields_args['buttons_shape'] === 'rounded') {
        Themify_Builder_Model::load_appearance_css($fields_args['buttons_shape']);
    }
}
if (!empty($fields_args['fullwidth_button'])) {
    $fields_args['display'] = '';
    $container_class[] = $fields_args['fullwidth_button'];
    Themify_Builder_Model::load_module_self_style($mod_name, 'fullwidth');
} elseif ($fields_args['display'] === 'buttons-vertical') {
    Themify_Builder_Model::load_module_self_style($mod_name, 'vertical');
}
if (!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active === false) {
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', self::parse_animation_effect($fields_args, array(
        'class' => implode(' ', $container_class),
    )), $fields_args, $mod_name, $element_id);
if (Themify_Builder::$frontedit_active === false) {
    $container_props['data-lazy'] = 1;
}
self::sticky_element_props($container_props,$fields_args);
?>
<!-- module buttons -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
<?php
$container_props = $container_class = $args = null;
echo Themify_Builder_Component_Module::get_module_title($fields_args, 'mod_title_button');

foreach ($fields_args['content_button'] as $content) {
    if(empty($content)){
        continue;
    }
    $content+=array(
        'label' => '',
        'link' => '',
        'icon' => '',
        'icon_alignment' => 'left',
        'link_options' => false,
        'lightbox_width' => '',
        'lightbox_height' => '',
        'lightbox_width_unit' => 'px',
        'lightbox_height_unit' => 'px',
        'button_color_bg' => 'tb_default_color',
        'title' => '',
        'id' => '',
        't' => 'i'
    );
    if ($content['button_color_bg'] === 'default') {
        $content['button_color_bg'] = 'tb_default_color';
    }
    $link_css_clsss = array('ui builder_button tf_in_flx');
    $link_attr = array();

    if ($content['link_options'] === 'lightbox') {
        $link_css_clsss[] = 'themify_lightbox';

        if ($content['lightbox_width'] !== '' || $content['lightbox_height'] !== '') {
            $lightbox_settings = array();
            if ($content['lightbox_width'] !== '') {
                $lightbox_settings[] = $content['lightbox_width'] . $content['lightbox_width_unit'];
            }
            if ($content['lightbox_height'] !== '') {
                $lightbox_settings[] = $content['lightbox_height'] . $content['lightbox_height_unit'];
            }
            $link_attr[] = sprintf('data-zoom-config="%s"', implode('|', $lightbox_settings));
            unset($lightbox_settings);
        }
    } elseif ($content['link_options'] === 'newtab') {
        $nofollow = $fields_args['nofollow_link'] === 'yes' ? 'nofollow ' : '';
        $link_attr[] = 'target="_blank" rel="' . $nofollow . 'noopener"';
    }
    $link_css_clsss[] = $content['button_color_bg'];
    if ($content['button_color_bg'] !== 'tb_default_color') {
        Themify_Builder_Model::load_color_css($content['button_color_bg']);
    }
    if ($fields_args['nofollow_link'] === 'yes' && $content['link_options'] !== 'newtab') {
        $link_attr[] = 'rel="nofollow"';
    }

    if ($fields_args['download_link'] === 'yes') {
        $link_attr[] = 'download';
    }
    $icon = $content['t'] !== 'l' ? ($content['icon'] ? themify_get_icon($content['icon']) : '') : themify_get_lottie($content, 'parent');
    if ($icon !== '') {
        $icon = sprintf('<em>%s</em>', $icon);
    }
    ?>
        <div class="module-buttons-item tf_in_flx">
        <?php if ($content['link'] !== ''): ?>
                <a href="<?php echo esc_url($content['link']) ?>" class="<?php echo implode(' ', $link_css_clsss) ?>" <?php echo implode(' ', $link_attr) ?><?php echo $content['title'] !== '' ? ' title="' . esc_attr($content['title']) . '"' : ''; ?><?php echo $content['id'] ? ' id="' . esc_attr($content['id']) . '"' : ''; ?>>
        <?php endif; ?>
            <?php if ($icon !== '' && $content['icon_alignment'] !== 'right'): ?>
                <?php echo $icon ?>
            <?php endif; ?>
            <?php if ($content['link'] !== ''): ?>
                <?php echo $content['label'] ?>
            <?php else:?>
                <?php echo $content['label'] ?>
            <?php endif; ?>
            <?php if ($icon !== '' && $content['icon_alignment'] === 'right'): ?>
                <?php echo $icon ?>
            <?php endif; ?>
        <?php if ($content['link'] !== ''): ?>
        </a>
        <?php endif; ?>
        </div>
            <?php } ?>
</div>
<!-- /module buttons -->
