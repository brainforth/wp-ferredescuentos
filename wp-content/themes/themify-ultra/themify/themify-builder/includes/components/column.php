<?php
defined('ABSPATH') || exit;

class Themify_Builder_Component_Column{

    /**
     * Get template column.
     * 
     * @param array $col 
     * @param string $builder_id 
     * @param boolean $echo 
     */
    public static function template(array &$col, $builder_id,bool $echo = true,bool $is_SubCol = false, $cl = null) {
        $print_column_classes = array('module_column');
        $print_column_classes[] = $is_SubCol === false ? 'tb-column' : 'sub_column';
        if (isset($col['grid_class'])) {
            $print_column_classes[] = strtr($col['grid_class'], array('first' => '', 'last' => ''));
        }
        $column_tag_attrs = array();
        $is_styling = !empty($col['styling']);
        $video_data = '';
        if (Themify_Builder::$frontedit_active === false) {
            $print_column_classes[] = 'tb_' . $col['element_id'];
            $column_tag_attrs['data-lazy'] = 1;
        }
        if ($is_styling === true) {
            if (!empty($col['styling']['global_styles'])) {
                Themify_Global_Styles::add_class_to_components($print_column_classes, $col['styling'], $builder_id);
            }
            if (isset($col['styling']['background_type'], $col['styling']['background_zoom']) && $col['styling']['background_type'] === 'image' && $col['styling']['background_zoom'] === 'zoom' && $col['styling']['background_repeat'] === 'repeat-none') {
                $print_column_classes[] = 'themify-bg-zoom';
            }
            if (!empty($col['styling']['custom_css_column'])) {
                $print_column_classes[] = $col['styling']['custom_css_column'];
            }
            // background video
            $video_data = Themify_Builder_Component_Row::get_video_background($col['styling']);
            if ($video_data) {
                $video_data = ' ' . $video_data;
            } else {
                Themify_Builder_Component_Row::set_bg_mode($column_tag_attrs, $col['styling']);
            }
        }
        if ($cl !== null) {
            $print_column_classes[] = $cl;
        }
        $column_tag_attrs['class'] = implode(' ', $print_column_classes);
        if ($is_styling === true) {
            Themify_Builder_Component_Row::clickable_component($column_tag_attrs,$col['styling']);
        }
        unset($print_column_classes);
        if ($echo === false) {
            ob_start();
        }
        // Start Column Render ######
        ?>
        <div <?php echo themify_get_element_attributes($column_tag_attrs), $video_data; ?>>
        <?php
        $column_tag_attrs = $video_data = null;
        if ($is_styling === true) {
            do_action('themify_builder_background_styling', $builder_id, $col, 'column', '');
            Themify_Builder_Component_Row::background_styling($col, 'column', $builder_id);
        }
        ?>
            <?php if (!empty($col['modules'])){
                foreach ($col['modules'] as &$mod) {
                    if (isset($mod['mod_name'])) {
                        Themify_Builder_Component_Module::template($mod, $builder_id);
                    }
                    if (!empty($mod['cols'])) {// Check for Sub-rows
                        Themify_Builder_Component_SubRow::template($mod, $builder_id);
                    }
                }
                unset($mod);
            } ?>
        </div>
            <?php
            // End Column Render ######

            if ($echo === false) {
                return PHP_EOL . ob_get_clean() . PHP_EOL;
            }
        }
    }
    