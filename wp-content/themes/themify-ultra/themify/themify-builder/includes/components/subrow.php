<?php
defined('ABSPATH') || exit;

class Themify_Builder_Component_Subrow{

    /**
     * Get template Sub-Row.
     * 
     * @param array $mod 
     * @param string $builder_id 
     * @param boolean $echo 
     */
    public static function template(array &$mod, $builder_id,bool $echo = true) {
        $mod = apply_filters('tf_builder_subrow', $mod, $builder_id);
        $print_sub_row_classes = array('module_subrow themify_builder_sub_row tf_w');
        $subrow_tag_attrs = array();
        $count = 0;
        $video_data = '';
        $is_styling = !empty($mod['styling']);
        if ($is_styling === true) {
            if (!empty($mod['styling']['custom_css_subrow'])) {
                $print_sub_row_classes[] = $mod['styling']['custom_css_subrow'];
            }
            if (isset($mod['styling']['background_type'], $mod['styling']['background_zoom']) && $mod['styling']['background_type'] === 'image' && $mod['styling']['background_zoom'] === 'zoom' && $mod['styling']['background_repeat'] === 'repeat-none') {
                $print_sub_row_classes[] = 'themify-bg-zoom';
            }
            // background video
            $video_data = Themify_Builder_Component_Row::get_video_background($mod['styling']);
            if ($video_data) {
                $video_data = ' ' . $video_data;
            } else {
                Themify_Builder_Component_Row::set_bg_mode($subrow_tag_attrs, $mod['styling']);
            }
            if (!empty($mod['styling']['global_styles'])) {
                Themify_Global_Styles::add_class_to_components($print_sub_row_classes, $mod['styling'], $builder_id);
            }
        } else {
            $mod['styling'] = array();
        }
        if (Themify_Builder::$frontedit_active === false) {
            $count = !empty($mod['cols']) ? count($mod['cols']) : 0;
            if($count > 0 ){
                $print_sub_row_classes=array_merge($print_sub_row_classes,Themify_Builder_Component_Row::get_responsive_cols($mod));
            }
            $print_sub_row_classes[] = 'tb_' . $mod['element_id'];
            if ($is_styling === true) {
                Themify_Builder_Component_Module::sticky_element_props($subrow_tag_attrs, $mod['styling']);
            }
            $subrow_tag_attrs['data-lazy'] = 1;
        }
        $print_sub_row_classes = apply_filters('themify_builder_subrow_classes', $print_sub_row_classes, $mod, $builder_id);
        $subrow_tag_attrs['class'] = implode(' ', $print_sub_row_classes);
        $print_sub_row_classes = null;
        if ($is_styling === true) {
            Themify_Builder_Component_Row::clickable_component($subrow_tag_attrs,$mod['styling']);
        }
        $subrow_tag_attrs = apply_filters('themify_builder_subrow_attributes', Themify_Builder_Component_Module::parse_animation_effect($mod['styling'], $subrow_tag_attrs), $mod['styling'], $builder_id);

        if ($echo === false) {
            $output = PHP_EOL; // add line break
            ob_start();
        }
        // Start Sub-Row Render ######
        ?>
        <div <?php echo themify_get_element_attributes($subrow_tag_attrs), $video_data; ?>>
        <?php
            $subrow_tag_attrs = $video_data = null;
            if ($is_styling === true) {
                Themify_Builder_Component_Row::background_styling($mod, 'subrow', $builder_id);
            }
            if ($count > 0) {
                if (isset($mod['desktop_dir']) && $mod['desktop_dir'] === 'rtl') {//backward compatibility
                    $mod['cols'] = array_reverse($mod['cols']);
                }
                foreach ($mod['cols'] as $i => &$sub_col) {
                    $cl = $i === 0 ? 'first' : ($i === ($count - 1) ? 'last' : null); //backward compatibility
                    Themify_Builder_Component_Column::template($sub_col, $builder_id, true, true, $cl);
                }
                unset($sub_col);
            }
            if ($is_styling === true) {//always should be output after cols,otherwise grid can be broken
                do_action('themify_builder_background_styling', $builder_id, $mod, 'subrow', '');
            }
            ?>
        </div>
        <?php
            // End Sub-Row Render ######
            if ($echo === false) {
                return PHP_EOL . ob_get_clean() . PHP_EOL;
            }
        }
}
        