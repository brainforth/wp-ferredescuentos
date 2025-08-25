<?php
/**
 * Template Fancy Heading
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-fancy-heading.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined('ABSPATH') || exit;

$mod_name = $args['mod_name'];
$element_id = $args['module_ID'];
$fields_args=$args['mod_settings']+ array(
    'heading' => '',
    'heading_tag' => 'h1',
    'heading_link' => '',
    'sub_heading_link' => '',
    'sub_heading' => '',
    'text_alignment' => '',
    'inline_text' => '',
    'animation_effect' => '',
    'icon_type' => '',
    'image' => '',
    'icon' => '',
    'icon_c' => '',
    'css_class' => '',
    'divider' => 'yes'
);
$container_class = apply_filters('themify_builder_module_classes', array(
    'module',
    'module-' . $mod_name,
    $element_id,
    $fields_args['css_class']
    ), $mod_name, $element_id, $fields_args);
$is_inline = $fields_args['inline_text'] === '1';
if ($is_inline === true) {
    $container_class[] = 'inline-fancy-heading';
}
$hide_divider = 'no' === $fields_args['divider'];
if ($hide_divider===true) {
    $container_class[] = 'tb_hide_divider';
}
if (!empty($fields_args['global_styles']) && Themify_Builder::$frontedit_active === false) {
    $container_class[] = $fields_args['global_styles'];
}
$container_props = apply_filters('themify_builder_module_container_props', self::parse_animation_effect($fields_args, array(
        'class' => implode(' ', $container_class),
    )), $fields_args, $mod_name, $element_id);


$mainTag = '' !== $fields_args['heading_link'] ? 'a' : 'span';
$subTag = '' !== $fields_args['sub_heading_link'] ? 'a' : 'span';
$alignment = '';
if (!empty($fields_args['text_alignment'])) {
    $alignment = str_replace('themify-text-', '', $fields_args['text_alignment']);
    $alignment = ' tf_text' . $alignment[0];
}
if (Themify_Builder::$frontedit_active === false) {
    $container_props['data-lazy'] = 1;
}

$icon=$fields_args['icon_type']!=='image_icon' ? ($fields_args['icon_type']!=='l'?($fields_args['icon']!=='' ? themify_get_icon($fields_args['icon']):'') : themify_get_lottie($fields_args, 'parent')) : $fields_args['image'];
self::sticky_element_props($container_props, $fields_args);
$fields_args['heading_tag'] = esc_attr( $fields_args['heading_tag'] );
?>
<!-- module fancy heading -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php $container_props = $container_class = $args = null; ?>
    <<?php echo $fields_args['heading_tag']; ?> class="fancy-heading<?php echo $alignment; ?>">
    <span class="main-head <?php echo $is_inline === true ? 'tf_inline_b' : 'tf_block'; ?>">
        <?php if ('' !== $fields_args['heading_link']): ?>
            <a href="<?php echo $fields_args['heading_link'] ?>"><?php echo $fields_args['heading']; ?></a>
        <?php else: ?>
            <?php echo $fields_args['heading']; ?>
        <?php endif; ?>
    </span>

    <?php if (!empty($icon)) : ?>
        <span class="tb_fancy_heading_icon_wrap <?php echo $is_inline === true ? 'tf_inline_b' : 'tf_block'; ?>">
            <?php if ($hide_divider===false) : ?><span class="tb_fancy_heading_border tf_rel"></span><?php endif; ?>
            <span class="tb_fancy_heading_icon">
                <?php if ($fields_args['icon_type'] !== 'image_icon') : ?>
                    <em<?php if ('' !== $fields_args['icon_c'] && $fields_args['icon_type']==='icon'){ echo ' style="color:' . Themify_Builder_Stylesheet::get_rgba_color($fields_args['icon_c']) . '"';}?>><?php echo $icon; ?></em>
                <?php else: ?>
                    <img src="<?php echo esc_url($icon) ?>" alt="<?php echo esc_attr($fields_args['heading']); ?>">
                <?php endif; ?>
            </span>
            <?php if ($hide_divider===false) : ?><span class="tb_fancy_heading_border tf_rel"></span><?php endif; ?>
        </span>
    <?php endif; ?>

    <span class="sub-head <?php echo $is_inline === true ? 'tf_inline_b' : 'tf_block'; ?> tf_rel">
        <?php if ('' !== $fields_args['sub_heading_link']): ?>
            <a href="<?php echo $fields_args['sub_heading_link'] ?>"><?php echo $fields_args['sub_heading']; ?></a>
        <?php else: ?>
            <?php echo $fields_args['sub_heading']; ?>
        <?php endif; ?>
    </span>
    </<?php echo $fields_args['heading_tag']; ?>>
</div>
<!-- /module fancy heading -->
