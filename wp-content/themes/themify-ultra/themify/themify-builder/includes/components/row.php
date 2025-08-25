<?php
defined('ABSPATH') || exit;

class Themify_Builder_Component_Row{

    public static $isFirstRow = false;

    
    /**
     * Computes and returns data for Builder row or column video background.
     *
     * @since 2.3.3
     *
     * @param array $styling The row's or column's styling array.
     *
     * @return string Return video data if row/col has a background video, else return false.
     */
    public static function get_video_background(array $styling):string {
        if (!( isset($styling['background_type']) && 'video' === $styling['background_type'] && !empty($styling['background_video']) )) {
            return '';
        }
        $video_data = 'data-tbfullwidthvideo="' . esc_url(themify_https_esc($styling['background_video'])) . '"';

        // Will only be written if they exist, for backwards compatibility with global JS variable tbLocalScript.backgroundVideoLoop

        
        if (!empty($styling['background_video_options'])) {
            if (is_array($styling['background_video_options'])) {
                $video_data .= in_array('mute', $styling['background_video_options'], true) ? '' : ' data-mutevideo="unmute"';
                $video_data .= in_array('unloop', $styling['background_video_options'], true) ? ' data-unloopvideo="unloop"' : '';
                $video_data .= in_array('playonmobile', $styling['background_video_options'], true) ? ' data-playonmobile="play"' : '';
            } else {
                $video_data .= ( false !== stripos($styling['background_video_options'], 'mute') ) ? '' : ' data-mutevideo="unmute"';
                $video_data .= ( false !== stripos($styling['background_video_options'], 'unloop') ) ? ' data-unloopvideo="unloop"' : '';
                $video_data .= ( false !== stripos($styling['background_video_options'], 'playonmobile') ) ? ' data-playonmobile="play"' : '';
            }
        }
        return apply_filters('themify_builder_row_video_background', $video_data, $styling);
    }

    /**
     * Computes and returns the HTML a color overlay.
     *
     * @since 2.3.3
     *
     * @param array $styling The row's or column's styling array.
     *
     * @return bool Returns false if $styling doesn't have a color overlay. Otherwise outputs the HTML;
     */
    private static function do_color_overlay(array $styling):bool {

        $type = !isset($styling['cover_color-type']) || $styling['cover_color-type'] === 'color' ? 'color' : 'gradient';
        $is_empty = $type === 'color' ? empty($styling['cover_color']) : empty($styling['cover_gradient-gradient']);

        if ($is_empty === true) {
            $hover_type = !isset($styling['cover_color_hover-type']) || $styling['cover_color_hover-type'] === 'hover_color' ? 'color' : 'gradient';
            $is_empty_hover = $hover_type === 'color' ? empty($styling['cover_color_hover']) : empty($styling['cover_gradient_hover-gradient']);
        }
        if ($is_empty === false || $is_empty_hover === false) {
            echo '<span class="builder_row_cover tf_abs"></span>';
            return true;
        }
        return false;
    }

    

    /**
     * Get the frame type
     */
    private static function get_frame(array $settings, string $side):string {
        if ((!isset($settings["{$side}-frame_type"]) || $settings["{$side}-frame_type"] === $side . '-presets') && !empty($settings["{$side}-frame_layout"])) {
            return $settings["{$side}-frame_layout"] !== 'none' ? 'presets' : '';
        }
        if (isset($settings["{$side}-frame_type"]) && $settings["{$side}-frame_type"] === $side . '-custom' && !empty($settings["{$side}-frame_custom"])) {
            return 'custom';
        }
        return '';
    }

    private static function show_frame(array $styles, $printed = array()) {
        $breakpoints = array('desktop' => '') + themify_get_breakpoints();
        $sides = array('top', 'bottom', 'left', 'right');
        $output = '';
        foreach ($sides as $side) {
            if (!isset($printed[$side])) {
                foreach ($breakpoints as $bp => $v) {
                    $settings = 'desktop' === $bp ? $styles : (!empty($styles['breakpoint_' . $bp]) ? $styles['breakpoint_' . $bp] : array() );
                    if (!empty($settings) && self::get_frame($settings, $side)) {
                        $printed[$side] = true;
                        $frame_location = ( isset($settings["{$side}-frame_location"]) && $settings["{$side}-frame_location"] === 'in_front' ) ? $settings["{$side}-frame_location"] : '';
                        $cl = $side === 'left' || $side === 'right' ? 'tf_h' : 'tf_w';
                        $output .= '<span class="tb_row_frame tb_row_frame_' . $side . ' ' . $frame_location . ' tf_abs tf_hide tf_overflow ' . $cl . '"></span>';
                        break;
                    }
                }
            }
        }
        if (!empty($output)) {
            Themify_Builder_Model::loadCssModules('fr', THEMIFY_BUILDER_CSS_MODULES . 'frames.css', THEMIFY_VERSION);
            echo '<span class="tb_row_frame_wrap tf_overflow tf_abs" data-lazy="1">', $output, '</span>';
        }
        return $printed;
    }

    public static function background_styling(array $row, string $type, $builder_id) {
        // Background cover color
        if (!empty($row['styling'])) {
            $hasOverlay = false;
            if (!self::do_color_overlay($row['styling'])) {
                $breakpoints = themify_get_breakpoints();
                foreach ($breakpoints as $bp => $v) {
                    if (!empty($row['styling']['breakpoint_' . $bp]) && self::do_color_overlay($row['styling']['breakpoint_' . $bp])) {
                        $hasOverlay = true;
                        break;
                    }
                }
            } else {
                $hasOverlay = true;
            }
            // Background Slider
            self::do_slider_background($row, $type);
            $frames = array();
            $framesCount = 0;
            if (!empty($row['styling']['global_styles'])) {
                $used_gs = Themify_Global_Styles::get_used_gs($builder_id);
                if (!empty($used_gs)) {
                    $global_styles = explode(' ', $row['styling']['global_styles']);
                    if ($hasOverlay === false) {
                        $breakpoints = array('desktop' => '') + themify_get_breakpoints();
                    }
                    foreach ($global_styles as $cl) {
                        if (isset($used_gs[$cl])) {
                            if ($hasOverlay === false) {
                                foreach ($breakpoints as $bp => $v) {

                                    if (($bp === 'desktop' && self::do_color_overlay($used_gs[$cl])) || ($bp !== 'desktop' && !empty($used_gs[$cl]['breakpoint_' . $bp]) && self::do_color_overlay($used_gs[$cl]['breakpoint_' . $bp]))) {
                                        $hasOverlay = true;
                                        break;
                                    }
                                }
                            }
                            if ($framesCount !== 4) {
                                $frames = self::show_frame($used_gs[$cl], $frames);
                                $framesCount = count($frames);
                            }
                        }
                        if ($hasOverlay === true && $framesCount === 4) {
                            break;
                        }
                    }
                }
            }
            if ($framesCount !== 4) {
                self::show_frame($row['styling'], $frames);
            }
            if ($hasOverlay === true) {
                Themify_Builder_Model::loadCssModules('cover', THEMIFY_BUILDER_CSS_MODULES . 'cover.css', THEMIFY_VERSION);
            }
        }
    }

    public static function clickable_component(array &$attr, array $settings) {
        if (!empty($settings['_link'])) {
            $attr['data-tb_link'] = esc_url($settings['_link']);
            if ( isset( $settings['_link_n'] ) && $settings['_link_n'] === 'yes' ) {
                $attr['data-tb_link_new'] = '1';
            }
            if ( ! isset( $settings['_link_o'] ) || $settings['_link_o'] === 'no' ) {
                if (!isset($attr['class'])) {
                    $attr['class'] = '';
                }
                $attr['class'] .= ' tb_link_outline';
            }
            themify_enque_style('tf_clickablecomponent', THEMIFY_BUILDER_CSS_MODULES . 'clickable-component.css', null, THEMIFY_VERSION);
        }
    }

    public static function set_bg_mode(array &$attr, array $styling) {
        $breakpoints = array('desktop' => '') + themify_get_breakpoints();
        foreach ($breakpoints as $bp => $v) {
            $bg = '';
            if ($bp === 'desktop') {
                if (isset($styling['background_repeat'])) {
                    $bg = $styling['background_repeat'];
                }
            } elseif (isset($styling['breakpoint_' . $bp]['background_repeat'])) {
                $bg = $styling['breakpoint_' . $bp]['background_repeat'];
            }
            if ($bg === 'builder-parallax-scrolling' || $bg === 'builder-zoom-scrolling' || $bg === 'builder-zooming') {
                $bg = explode('-', $bg);
                $attr['data-' . $bg[1] . '-bg'] = $bp;
            }
        }
        return $attr;
    }

    public static function get_responsive_cols(array $row) {
        $cl = array();
        $isFullpage = function_exists('themify_theme_is_fullpage_scroll') && themify_theme_is_fullpage_scroll();
        if (!empty($row['sizes'])) {
            $_arr = array('align', 'gutter', 'auto_h', 'dir');
            foreach ($_arr as $k) {
                if (!empty($row['sizes']['desktop_' . $k])) {
                    $v = $row['sizes']['desktop_' . $k];
                    if ($k === 'align') {
                        if ($v === 'center') {
                            $v = 'col_align_middle';
                        } elseif ($v === 'end') {
                            $v = 'col_align_bottom';
                        } else {
                            $v = 'col_align_top';
                        }
                    } elseif ($k === 'gutter') {
                        $v = $v === 'narrow' || $v === 'none' ? 'gutter-' . $v : '';
                    } elseif ($k === 'auto_h') {
                        $v = $v == '1' ? 'col_auto_height' : '';
                    } elseif ($k === 'dir') {
                        $v = $v === 'rtl' ? 'direction_rtl' : '';
                    }
                    if ($v !== '') {
                        $cl[] = $v;
                    }
                } elseif ($k === 'align') {
                    $cl[] = $isFullpage === true ? 'col_align_middle' : 'col_align_top';
                }
            }
        } else {
            if (!empty($row['column_h'])) {
                $cl[] = 'col_auto_height';
            }
            if (!empty($row['gutter']) && $row['gutter'] !== 'gutter-default') {
                $cl[] = $row['gutter'];
            }
            if (isset($row['desktop_dir']) && $row['desktop_dir'] === 'rtl') {
                $cl[] = 'direction_rtl';
            }
            $cl[] = !empty($row['column_alignment']) ? $row['column_alignment'] : ($isFullpage === true ? 'col_align_middle' : 'col_align_top');
        }

        $cl[] = 'tb_col_count_' . count($row['cols']);

        return $cl;
    }

    /**
     * Computes and returns the HTML for a background slider.
     *
     * @since 2.3.3
     *
     * @param array  $row_or_col   Row or column definition.
     * @param string $order        Order of row/column (e.g. 0 or 0-1-0-1 for sub columns)
     * @param string $type Accepts 'row', 'col', 'sub-col'
     *
     * @return bool Returns false if $row_or_col doesn't have a bg slider. Otherwise outputs the HTML for the slider.
     */
    public static function do_slider_background(array $row_or_col, $type = 'row'):bool {
        if (!isset($row_or_col['styling']['background_type']) || 'slider' !== $row_or_col['styling']['background_type'] || empty($row_or_col['styling']['background_slider'])) {
            return false;
        }
        $images = themify_get_gallery_shortcode($row_or_col['styling']['background_slider']);
        if (!empty($images)) :

            $size = isset($row_or_col['styling']['background_slider_size']) ? $row_or_col['styling']['background_slider_size'] : false;
            if (!$size) {
                $size = themify_get_gallery_shortcode_params($row_or_col['styling']['background_slider'], 'size');
                if (!$size) {
                    $size = 'large';
                }
            }
            $bgmode = !empty($row_or_col['styling']['background_slider_mode']) ? $row_or_col['styling']['background_slider_mode'] : 'fullcover';
            $slider_speed = !empty($row_or_col['styling']['background_slider_speed']) ? $row_or_col['styling']['background_slider_speed'] : '2000';
            ?>
            <span class="tf_hide <?php echo $type; ?>-slider tb_slider tf_abs" data-bgmode="<?php echo $bgmode; ?>" data-sliderspeed="<?php echo $slider_speed ?>">
                <span class="tf_abs row-slider-slides tf_w tf_hidden tf_clearfix">
            <?php
            foreach ($images as $i => $img) {
                $img_data = wp_get_attachment_image_src($img->ID, $size);
                if (empty($img_data)) {
                    continue;
                }
                $alt = get_post_meta($img->ID, '_wp_attachment_image_alt', TRUE);
                ?>
                    <span data-bg="<?php echo esc_url(themify_https_esc($img_data[0])); ?>"<?php if (!empty($alt)): ?> data-bg-alt="<?php esc_attr_e($alt); ?>"<?php endif; ?>>
                        <a href="javascript:;" rel="nofollow" class="row-slider-dot" data-index="<?php echo $i; ?>"><span class="screen-reader-text">&bull;</span></a>
                    </span>
                <?php
            }
            ?>
                </span>
                <span class="row-slider-nav tf_abs_t tf_w">
                    <a href="javascript:;" rel="nofollow" class="row-slider-arrow row-slider-prev tf_hidden tf_abs_t"><span class="screen-reader-text">&larr;</span></a>
                    <a href="javascript:;" rel="nofollow" class="row-slider-arrow row-slider-next tf_hidden tf_abs_t"><span class="screen-reader-text">&rarr;</span></a>
                </span>
            </span>
            <?php
            return true;
        endif; // images
        return false;
    }

    /**
     * Get template row
     *
     * @param array  $row
     * @param string $builder_id
     * @param bool   $echo
     *
     * @return string
     */
    public static function template(&$row, $builder_id, $echo = true, $tmp = -1) {//4 argument isn't used need for backward to avoid fatal error
        if ($tmp === true || $tmp === false) {//map old data arguments
            $row = $builder_id;
            $builder_id = $echo;
            $echo = $tmp;
        }
        $row = apply_filters('tf_builder_row', $row, $builder_id);
        // prevent empty rows from being rendered
        if (Themify_Builder::$frontedit_active === false || Themify_Builder::$is_loop === true) {
            $count = isset($row['cols']) ? count($row['cols']) : 0;
            if (($count === 0 && !isset($row['styling']) ) || ($count === 1 && empty($row['cols'][0]['modules']) && empty($row['cols'][0]['styling']) && empty($row['styling']) ) // there's only one column and it's empty
            ) {
                return '';
            }
            /* allow addons to control the display of the rows */
            $display = apply_filters('themify_builder_row_display', true, $row, $builder_id);
            if (false === $display || (isset($row['styling']['visibility_all']) && $row['styling']['visibility_all'] === 'hide_all' )) {
                return '';
            }
        } else {
            $count = 0;
        }
        $row_classes = array('module_row themify_builder_row');
        $row_attributes = array();
        $is_styling = !empty($row['styling']);
        $video_data = '';
        if ($is_styling === true) {
            if (!isset($row['styling']['background_type']) && !empty($row['styling']['background_video'])) {
                $row['styling']['background_type'] = 'video';
            } elseif ((!isset($row['styling']['background_type']) || $row['styling']['background_type'] === 'image' ) && isset($row['styling']['background_zoom']) && $row['styling']['background_zoom'] === 'zoom' && $row['styling']['background_repeat'] === 'repeat-none') {
                $row_classes[] = 'themify-bg-zoom';
            }
            $class_fields = array('custom_css_row', 'row_height');
            foreach ($class_fields as $field) {
                if (!empty($row['styling'][$field])) {
                    $row_classes[] = $row['styling'][$field];
                }
            }
            unset($class_fields);
            /**
             * Row Width class
             * To provide backward compatibility, the CSS classname and the option label do not match. See #5284
             */
            if (isset($row['styling']['row_width'])) {
                if ('fullwidth' === $row['styling']['row_width']) {
                    $row_classes[] = 'fullwidth_row_container';
                } elseif ('fullwidth-content' === $row['styling']['row_width']) {
                    $row_classes[] = 'fullwidth';
                }
                $breakpoints = themify_get_breakpoints('', true);
                $breakpoints['desktop'] = 1;
                $prop = 'fullwidth' === $row['styling']['row_width'] ? 'padding' : 'margin';
                foreach ($breakpoints as $k => $v) {
                    $styles = $k === 'desktop' ? $row['styling'] : (!empty($row['styling']['breakpoint_' . $k]) ? $row['styling']['breakpoint_' . $k] : false);
                    if ($styles) {
                        $val = self::getDataValue($styles, $prop);
                        if ($val) {
                            $row_attributes['data-' . $k . '-' . $prop] = $val;
                        }
                    }
                }
                $breakpoints = null;
            }
            // background video
            $video_data = self::get_video_background($row['styling']);
            if ($video_data) {
                $video_data = ' ' . $video_data;
            } else {
                self::set_bg_mode($row_attributes, $row['styling']);
            }
            // Class for Scroll Highlight
            if (!empty($row['styling']['row_anchor']) && $row['styling']['row_anchor'] !== '#') {
                $row_classes[] = 'tb_has_section';
                $row_classes[] = 'tb_section-' . $row['styling']['row_anchor'];
                $row_attributes['data-anchor'] = $row['styling']['row_anchor'];
            }
            // Disable change hashtag in URL
            if (!empty($row['styling']['hide_anchor'])) {
                $row_attributes['data-hide-anchor'] = $row['styling']['hide_anchor'];
            }
            if (!empty($row['styling']['global_styles'])) {
                Themify_Global_Styles::add_class_to_components($row_classes, $row['styling'], $builder_id);
            }
        } else {
            $row['styling'] = array();
        }
        if ($echo === false) {
            $output = PHP_EOL; // add line break
            ob_start();
        }
        if (Themify_Builder::$frontedit_active === false) {
            $row_content_classes = $count > 0 ? self::get_responsive_cols($row) : array();
            $row_content_classes = implode(' ', $row_content_classes);
            if (isset($row['styling']['row_width']) && ('fullwidth' === $row['styling']['row_width'] || 'fullwidth-content' === $row['styling']['row_width'])) {
                $row_attributes['data-css_id'] = $row['element_id'];
            }
            $row_classes[] = 'tb_' . $row['element_id'];
            if ($is_styling === true) {
                Themify_Builder_Component_Module::sticky_element_props($row_attributes, $row['styling']);
            }
            $row_attributes['data-lazy'] = 1;
            if (self::$isFirstRow === false) {
                self::$isFirstRow = true;
                $row_classes[] = 'tb_first'; //need for lazy loadd, load first row bg image
            } else {
                self::$isFirstRow = null;
            }
        }
        do_action('themify_builder_row_start', $builder_id, $row, '');
        $row_classes[] = 'tf_w';
        $row_attributes['class'] = implode(' ', apply_filters('themify_builder_row_classes', $row_classes, $row, $builder_id));
        $row_classes = null;
        self::clickable_component($row_attributes, $row['styling']);
        $row_attributes = apply_filters('themify_builder_row_attributes', Themify_Builder_Component_Module::parse_animation_effect($row['styling'], $row_attributes), $row['styling'], $builder_id);
        ?>
        <?php if (strpos($row_attributes['class'], 'tb-page-break') !== false): ?>
            <!-- tb_page_break -->
        <?php endif; ?>
        <div <?php echo themify_get_element_attributes($row_attributes), $video_data; ?>>
            <?php
            $row_attributes = $video_data = null;
            if ($is_styling === true) {
                do_action('themify_builder_background_styling', $builder_id, $row, 'row', '');
                self::background_styling($row, 'row', $builder_id);
            }
            ?>
            <div class="row_inner<?php if (Themify_Builder::$frontedit_active === false): ?> <?php echo $row_content_classes ?><?php endif; ?> tf_box tf_rel">
                <?php
                unset($row_content_classes);
                if ($count > 0) {
                    if (isset($row['desktop_dir']) && $row['desktop_dir'] === 'rtl') {//backward compatibility
                        $row['cols'] = array_reverse($row['cols']);
                    }
                    foreach ($row['cols'] as $i => &$col) {
                        $cl = $i === 0 ? 'first' : ($i === ($count - 1) ? 'last' : null); //backward compatibility
                        Themify_Builder_Component_Column::template($col, $builder_id, true, false, $cl);
                    }
                    unset($col);
                }
                ?>
            </div>
        </div>
        <?php
        do_action('themify_builder_row_end', $builder_id, $row, '');
        if ($echo === false) {
            return PHP_EOL . ob_get_clean() . PHP_EOL;
        }
    }

    private static function getDataValue(array $styles,string $type = 'padding'):string {
        $value = '';
        if (!empty($styles['checkbox_' . $type . '_apply_all']) && !empty($styles[$type . '_top'])) {
            $value = $styles[$type . '_top'];
            $value .= isset($styles[$type . '_top_unit']) ? $styles[$type . '_top_unit'] : 'px';
            $value = $value . ',' . $value;
        } elseif (!empty($styles[$type . '_left']) || !empty($styles[$type . '_right'])) {
            if (!empty($styles[$type . '_left'])) {
                $value = $styles[$type . '_left'];
                $value .= isset($styles[$type . '_left_unit']) ? $styles[$type . '_left_unit'] : 'px';
            }
            if (!empty($styles[$type . '_right'])) {
                $value .= ',' . $styles[$type . '_right'];
                $value .= isset($styles[$type . '_right_unit']) ? $styles[$type . '_right_unit'] : 'px';
            }
        }
        return $value;
    }
}
