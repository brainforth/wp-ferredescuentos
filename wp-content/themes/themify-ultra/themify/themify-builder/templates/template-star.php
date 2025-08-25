<?php
/**
 * Template star
 *
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-star.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined('ABSPATH') || exit;

$mod_name = $args['mod_name'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'm_t' => '',
    'rates' => [],
    'css' => '',
    'animation_effect' => '',
);
$container_class = apply_filters('themify_builder_module_classes', array(
    'module',
    'module-' . $mod_name,
    $element_id,
    $fields_args['css']
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
<!-- module star -->
<div <?php echo themify_get_element_attributes($container_props); ?>>
    <?php
    echo Themify_Builder_Component_Module::get_module_title($fields_args, 'm_t');
    $rates = $fields_args['rates']; 
    $container_props = $container_class = $args = $fields_args=null;
    ?>
    <div class="tb_star_wrap">
        <?php foreach ($rates as $i => $r): ?>
            <div class="tb_star_item">
                <?php
                $count = !empty($r['count']) ? (int) $r['count'] : 5;
                $rating = isset($r['rating']) ? round((float) $r['rating'], 2) : 5;
                $icon = isset($r['ic']) ? $r['ic'] : 'fas fullstar';
                $defaultIcon = themify_get_icon($icon);
                $fillIcon = themify_get_icon($icon, false, false, false, array('class' => 'tb_star_fill'));
                ?>
                <?php if (isset($r['text_b'])): ?>
                    <span class="tb_star_text_b"><?php echo $r['text_b'] ?></span>
                <?php endif; ?>
                <div class="tb_star_container">
                    <?php
                    for ($j = 0; $j < $count; ++$j) {
                        if (($rating - $j) >= 1) {
                            echo $fillIcon;
                        } elseif ($rating > $j) {
                            $decimal = $rating - (int) $rating;
                            $gid = $element_id . $i;
                            ?>
                            <svg width="0" height="0" aria-hidden="true" style="visibility:hidden;position:absolute">
                            <defs>
                            <linearGradient id="<?php echo $gid ?>">
                            <stop offset="<?php echo $decimal * 100 ?>%" class="tb_star_fill"/>
                            <stop offset="<?php echo $decimal * 100 ?>%" stop-color="currentColor"/>
                            </linearGradient>
                            </defs>
                            </svg>
                            <?php
                            echo themify_get_icon($icon, false, false, false, array('class' => 'tb_star_half', 'style' => '--tb_star_half:url(#' . $gid . ')'));
                        } else {
                            echo $defaultIcon;
                        }
                    }
                    ?>
                </div>
                <?php if (isset($r['text_a'])): ?>
                    <span class="tb_star_text_a"><?php echo $r['text_a'] ?></span>
            <?php endif; ?>
            </div>
<?php endforeach; ?>
    </div>
</div>
<!-- /module star -->
