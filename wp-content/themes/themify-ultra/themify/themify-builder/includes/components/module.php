<?php

/**
 * This file contain abstraction class to create module object.
 *
 * Themify_Builder_Component_Module class should be used as main class and
 * create any child extend class for module.
 * 
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */
defined('ABSPATH') || exit;

/**
 * The abstract class for Module.
 *
 * Abstraction class to initialize module object, don't initialize
 * this class directly but please create child class of it.
 *
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 * @author     Themify
 */
class Themify_Builder_Component_Module {
    
    /**
     * Module Name.
     * 
     * @access public
     * @var string $name
     */
    public $name = ''; //deprecated

    /**
     * Module Slug.
     * 
     * @access public
     * @var string $slug
     */
    public $slug = ''; //deprecated

    /**
     * Module Category.
     *
     * @access public
     * @var string $category
     */
    public $category = null; //deprecated

    private static $assets = array();
    public static $isFirstModule = false;
    public static $disable_inline_edit = false;

    public static function get_module_class(string $slug):string{
        $name=\explode('-',$slug);
        $items=['TB'];
        foreach($name as $v){
            $items[]= \ucfirst($v);
        }
        $items[]='Module';
        $className= \implode('_',$items);
        if(!class_exists('\\'.$className,false)){//bakward
            if($slug==='pro-image'){
                $className= 'TB_Image_Pro_Module';
            }
            elseif($slug==='typewriter'){
                $className= 'TB_Typewriter';
            }
            elseif($slug==='stats'){
                $className= 'TB_Statistics_Module';
            }
            elseif($slug==='readtime'){
                $className= 'TB_Read_Time_Module';
            }
            elseif($slug==='ab-image'){
                $className= 'TB_AB_Image_Module';
            }
        }
        return '\\'.$className;
    }

    public static function get_js_css():array {
        return array();
    }

    public static function get_module_icon():string {
        return '';
    }

    public static function get_module_name():string {
        return '';
    }

    public static function is_available():bool{
        return true;
    }

    public static function get_json_file():array {
        return array();
    }

    public static function get_styles_json():array {
        $keys=array();
        $loadFiles=apply_filters('tb_json_files', []);
        $res=[THEMIFY_BUILDER_URI.'/json/style.json?ver='.THEMIFY_VERSION];
        foreach($loadFiles as $file){
            $res[]=$file['f'].'?ver='.$file['v'];
        }
        unset($loadFiles);
        $modules=self::load_modules();
        foreach ($modules as $id=>$module) {
            $file=$module::get_json_file();
            if(!empty($file)){
                $files[]=$file;
                $keys[]=$id.$file['v'];
            }
        }
        if(!empty($files)){
            if ( ! themify_is_dev_mode() && count( $files ) > 2 ) {
                sort($keys);
                $jsonDir='/json/';
                $jsonName=$jsonDir.crc32(implode('',$keys)).'.json';
                $jsonF=THEMIFY_BUILDER_DIR. $jsonName;
                unset($keys);
                $hasError=false;
                if (!is_file($jsonF)) {
                    $jsonData=$fallback=[];
                    foreach($files as $file){
                        $content=$hasError===false?Themify_Filesystem::get_file_content($file['f']):null;
                        if(!empty($content)){
                            $content=json_decode($content,true);
                            if(!empty($content)){
                                $jsonData=array_merge($jsonData, $content);
                            }
                        }
                        if($hasError===false && empty($content)){
                            $hasError=true;
                            $jsonData=null;
                        }
                        $fallback[]=$file['f'].'?ver='.$file['v'];
                    }
                    if($hasError===false){
                        $tmpF=THEMIFY_BUILDER_DIR.$jsonDir. uniqid('tmp_');
                        $jsonData=json_encode($jsonData);
                        if(empty($jsonData) || !file_put_contents($tmpF, $jsonData) || !rename($tmpF,$jsonF)){
                            $hasError=true;
                            if(is_file($tmpF)){
                                unlink($tmpF);
                            }
                        }
                        $jsonData=null;
                    }
                    if($hasError===true){
                        $res=array_merge($res,$fallback);
                    }
                    unset($fallback);
                }
                if($hasError===false){
                    $res[]=THEMIFY_BUILDER_URI.$jsonName.'?ver='.THEMIFY_VERSION;
                }
            }
            else{
                foreach($files as $file){
                    $res[]=$file['f'].'?ver='.$file['v'];
                }
            }
        }
        return $res;
    }
    
    //will be need later
    public static function add_inline_edit_icon() {

    }
    
    /**
     * Get checkbox data
     * @param $setting
     * @return string
     */
    public static function get_checkbox_data(string $setting):string {
        return \implode(' ', \explode('|', $setting));
    }
    
    /**
     * Return only value setting
     * @param $string
     * @return string
     */
    public static function get_param_value(string $string):string {
        return \explode('|', $string)[0];
    }

    
    /**
     * Retrieve builder templates
     * @param $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     * @param bool $echo
     * @return string|VOID
     */
    public static function retrieve_template(string $template_name,$args = array(),string $template_path = '', string $default_path = '', bool $echo = true) {
        if ($echo === false) {
            ob_start();
        }
        self::get_template($template_name, $args, $template_path, $default_path);
        if ($echo === false) {
            return ob_get_clean();
        }
    }

    /**
     * Get template builder
     * @param $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     */
    protected static function get_template(string $template_name, &$args = array(),string $template_path = '',string $default_path = '') {
        static $paths = array();
        $key = $template_path . $template_name;
        if (!isset($paths[$key])) {
            $paths[$key] = self::locate_template($template_name, $template_path, $default_path);
        }
        if (isset($paths[$key]) && $paths[$key] !== '') {
            global $ThemifyBuilder;//deprecated
            include($paths[$key]);
        }
    }

    /**
     * Locate a template and return the path for inclusion.
     *
     * This is the load order:
     *
     *      yourtheme       /   $template_path  /   $template_name
     *      $default_path   /   $template_name
     */
    public static function locate_template(string $template_name,string $template_path = '',string $default_path = ''):string {
        static $theme_dir = null;
        static $child_dir = null;
        $template = '';

        $DS = DIRECTORY_SEPARATOR;
        if ($theme_dir === null) {
            $builderDir = $DS . 'themify-builder' . $DS;
            $theme_dir = get_template_directory() . $builderDir;
            if (!\is_dir($theme_dir)) {
                $theme_dir = false;
            }
            if (is_child_theme()) {
                $child_dir = get_stylesheet_directory() . $builderDir;
                if (!\is_dir($child_dir)) {
                    $child_dir = false;
                }
            }
            $builderDir = null;
        }
        if ($theme_dir !== false || $child_dir !== null || $child_dir !== false) {
            $templates = array();
            if ($child_dir !== null && $child_dir !== false) {
                $templates[] = $child_dir;
            }
            if ($theme_dir !== false) {
                $templates[] = $theme_dir;
            }
            foreach ($templates as $dir) {//is theme file
                if (\is_file($dir . $template_name)) {
                    $template = $dir . $template_name;
                    break;
                }
            }
            unset($templates);
        }
        if ($template === '') {
            if ($template_path === '') {
                $modulesPath = \Themify_Builder_Model::get_directory_path();
                if (\strpos($template_name, 'template-') === 0) {
                    $module = str_replace(array('template-', '.php'), '', $template_name);
                    if (isset($modulesPath[$module])) {
                        $template = pathinfo($modulesPath[$module], PATHINFO_DIRNAME) . $DS . 'templates' . $DS . $template_name;
                    }
                }
                if ($template === '') {
                    $dir = rtrim(THEMIFY_BUILDER_TEMPLATES_DIR, $DS) . $DS;
                    if (\is_file($dir . $template_name)) {
                        $template = $dir . $template_name;
                    }
                    // Get default template
                    if ($template === '') {
                        foreach ($modulesPath as $m) {//backward
                            $dir = pathinfo($m, PATHINFO_DIRNAME) . $DS . 'templates' . $DS . $template_name;
                            if (\is_file($dir)) {
                                $template = $dir;
                                break;
                            }
                        }
                        if ($template === '') {
                            $template = $default_path . $template_name;
                            if (\is_file($template)) {
                                $template = '';
                            }
                        }
                    }
                }
            } else {
                $template = \rtrim($template_path, $DS) . $DS . $template_name;
            }
        }
        // Return what we found
        return apply_filters('themify_builder_locate_template', $template, $template_name, $template_path);
    } 


    /**
     * Sticky Element props attributes
     * @param array $props
     * @param array $fields_args
     * @param string $mod_name
     * @param string $module_ID
     * @return array
     */
    public static function sticky_element_props(array &$props,array $fields_args) {
        if (!empty($fields_args['stick_at_check']) || !empty($fields_args['stick_at_check_t']) || !empty($fields_args['stick_at_check_tl']) || !empty($fields_args['stick_at_check_m'])) {
            static $is_sticky = null;
            if ($is_sticky === null) {
                $is_sticky = \Themify_Builder_Model::is_sticky_scroll_active();
            }
            if ($is_sticky !== false) {
                $_arr = array('d', 'tl', 't', 'm');
                $settings = array();
                foreach ($_arr as $v) {
                    $key = $v === 'd' ? '' : '_' . $v;
                    if (($key === '' && !empty($fields_args['stick_at_check'])) || ($key !== '' && isset($fields_args['stick_at_check' . $key]) && $fields_args['stick_at_check' . $key] !== '')) {
                        $settings[$v] = array();
                        if ($key !== '' && $fields_args['stick_at_check' . $key] !== '1') {
                            $settings[$v] = 0;
                        } else {
                            if (isset($fields_args['stick_at_position' . $key]) && $fields_args['stick_at_position' . $key] === 'bottom') {
                                $settings[$v]['stick'] = array();
                                $settings[$v]['stick']['p'] = $fields_args['stick_at_position' . $key];
                            }
                            if (!empty($fields_args['stick_at_pos_val' . $key])) {
                                if (!isset($settings[$v]['stick'])) {
                                    $settings[$v] = array('stick' => array());
                                }
                                $settings[$v]['stick']['v'] = $fields_args['stick_at_pos_val' . $key];
                                if (isset($fields_args['stick_at_pos_val_unit' . $key]) && $fields_args['stick_at_pos_val_unit' . $key] !== 'px') {
                                    $settings[$v]['stick']['u'] = $fields_args['stick_at_pos_val_unit' . $key];
                                }
                            }

                            if (!empty($fields_args['unstick_when_check' . $key])) {
                                $unstick = array();
                                if (isset($fields_args['unstick_when_element' . $key]) && $fields_args['unstick_when_element' . $key] !== 'builder_end') {
                                    if (isset($fields_args['unstick_when_condition' . $key]) && $fields_args['unstick_when_condition' . $key] !== 'hits') {
                                        $unstick['r'] = $fields_args['unstick_when_condition' . $key];
                                    }
                                    $unstick['type'] = $fields_args['unstick_when_element' . $key];
                                    if ($unstick['type'] === 'row' && isset($fields_args['unstick_when_el_row_id' . $key]) && $fields_args['unstick_when_el_row_id' . $key] !== 'row') {
                                        $unstick['el'] = $fields_args['unstick_when_el_row_id' . $key];
                                    } elseif ($unstick['type'] === 'module' && !empty($fields_args['unstick_when_el_mod_id' . $key])) {
                                        $unstick['el'] = $fields_args['unstick_when_el_mod_id' . $key];
                                    } else {
                                        continue;
                                    }

                                    if (isset($fields_args['unstick_when_pos' . $key]) && $fields_args['unstick_when_pos' . $key] !== 'this') {
                                        $unstick['cur'] = $fields_args['unstick_when_pos' . $key];
                                        if (!empty($fields_args['unstick_when_pos_val' . $key])) {
                                            $unstick['v'] = $fields_args['unstick_when_pos_val' . $key];
                                            if (isset($fields_args['unstick_when_pos_val_unit' . $key]) && $fields_args['unstick_when_pos_val_unit' . $key] !== 'px') {
                                                $unstick['u'] = $fields_args['unstick_when_pos_val_unit' . $key];
                                            }
                                        }
                                    }
                                } else {
                                    $unstick['type'] = 'builder';
                                }
                                if (!empty($unstick)) {
                                    $settings[$v]['unstick'] = $unstick;
                                }
                            }
                        }
                    }
                }
                if (!empty($settings)) {
                    unset($_arr);
                    $props['data-sticky-active'] = \json_encode($settings);
                    if ($is_sticky !== 'done') {
                        $is_sticky = 'done';
                        \Themify_Enqueue_Assets::addPrefetchJs(THEMIFY_BUILDER_JS_MODULES . 'sticky.js', THEMIFY_VERSION);
                    }
                }
            }
        }//Add custom attributes html5 data to module container div to show parallax options.
        elseif (Themify_Builder::$frontedit_active === false && (!empty($fields_args['motion_effects']) || !empty($fields_args['custom_parallax_scroll_speed']) )) {
            static $is_lax = null;
            if ($is_lax === null) {
                $is_lax = \Themify_Builder_Model::is_scroll_effect_active();
            }
            if ($is_lax !== false) {
                $has_lax = false; /* validate Lax settings */
                // Check settings from Floating tab to apply them to Lax library
                if (!empty($fields_args['custom_parallax_scroll_speed'])) {
                    $has_lax = true;
                    $props['data-parallax-element-speed'] = $fields_args['custom_parallax_scroll_speed'];

                    $speed = self::map_animation_speed($fields_args['custom_parallax_scroll_speed']);

                    if (!isset($fields_args['custom_parallax_scroll_reverse']) || $fields_args['custom_parallax_scroll_reverse'] === '|') {
                        $speed = '-' . $speed;
                    }
                    $props['data-lax-translate-y'] = 'vh 1,0 ' . $speed;
                    if (!empty($fields_args['custom_parallax_scroll_fade']) && $fields_args['custom_parallax_scroll_fade'] !== '|') {
                        $props['data-lax-opacity'] = 'vh 1,0 0';
                    }
                }
                // Add motion effects from Motion tab
                $effects=isset($fields_args['motion_effects'])?$fields_args['motion_effects']:array();
                if (!isset($effects['t'])) {
                    $props['data-lax-optimize'] = 'true';
                }
                // Vertical
                if (!empty($effects['v']['val']['v_dir'])) {
                    $has_lax = true;
                    $v_speed = isset($effects['v']['val']['v_speed']) ? $effects['v']['val']['v_speed'] : 1;
                    $v_speed = self::map_animation_speed($v_speed);
                    $viewport = isset($effects['v']['val']['v_vp']) ? explode(',', $effects['v']['val']['v_vp']) : array(0, 100);
                    $bottom = 1 - ( (int) $viewport[0] / 100 );
                    $top = 1 - ( (int) $viewport[1] / 100 );
                    $props['data-lax-translate-y'] = $effects['v']['val']['v_dir'] === 'up' ? '(vh*' . $bottom . ') 0,(vh*' . $top . ') -' . $v_speed : '(vh*' . $bottom . ') 0,(vh*' . $top . ') ' . $v_speed;
                }
                // Horizontal
                if (!empty($effects['h']['val']['h_dir'])) {
                    $has_lax = true;
                    $h_speed = isset($effects['h']['val']['h_speed']) ? $effects['h']['val']['h_speed'] : 9;
                    $h_speed=self::map_animation_speed($h_speed);
                    $viewport = isset($effects['h']['val']['h_vp']) ? explode(',', $effects['h']['val']['h_vp']) : array(0, 100);
                    $bottom = 1 - ( (int) $viewport[0] / 100 );
                    $top = 1 - ( (int) $viewport[1] / 100 );
                    $props['data-lax-translate-x'] = $effects['h']['val']['h_dir'] === 'toleft' ? '(vh*' . $bottom . ') 0,(vh*' . $top . ') -' . $h_speed : '(vh*' . $bottom . ') 0,(vh*' . $top . ') ' . $h_speed;
                }
                // Opacity
                if (!empty($effects['t']['val']['t_dir'])) {
                    $has_lax = true;
                    $viewport = isset($effects['t']['val']['t_vp']) ? explode(',', $effects['t']['val']['t_vp']) : array(0, 100);
                    $bottom = 1 - ( (int) $viewport[0] / 100 );
                    $top = 1 - ( (int) $viewport[1] / 100 );
                    $center = ( $bottom - ( ( $bottom - $top ) / 2 ) );
                    if ($effects['t']['val']['t_dir'] === 'fadein') {
                        $props['data-lax-opacity'] = '(vh*' . $bottom . ') 0,(vh*' . $top . ') 1';
                    } elseif ($effects['t']['val']['t_dir'] === 'fadeout') {
                        $props['data-lax-opacity'] = '(vh*' . $bottom . ') 1,(vh*' . $top . ') 0';
                    } elseif ($effects['t']['val']['t_dir'] === 'fadeoutin') {
                        $props['data-lax-opacity'] = '(vh*' . $bottom . ') 1,(vh*' . $center . ') 0,(vh*' . $top . ') 1';
                    } elseif ($effects['t']['val']['t_dir'] === 'fadeinout') {
                        $props['data-lax-opacity'] = '(vh*' . $bottom . ') 0,(vh*' . $center . ') 1,(vh*' . $top . ') 0';
                    }
                } elseif (!isset($fields_args['animation_effect_delay'])) {
                    unset($props['data-lax-opacity'], $props['data-lax-optimize']);
                }
                // Blur
                if (!empty($effects['b']['val']['b_dir'])) {
                    $has_lax = true;
                    $b_level = isset($effects['b']['val']['b_level']) ? $effects['b']['val']['b_level']: 5;
                    $b_level=self::map_animation_speed($b_level, 'blur');
                    $viewport = isset($effects['b']['val']['b_vp']) ? explode(',', $effects['b']['val']['b_vp']) : array(0, 100);
                    $bottom = 1 - ( (int) $viewport[0] / 100 );
                    $top = 1 - ( (int) $viewport[1] / 100 );
                    $props['data-lax-blur'] = $effects['b']['val']['b_dir'] === 'fadein' ? '(vh*' . $bottom . ') ' . $b_level . ',(vh*' . $top . ') 0' : '(vh*' . $bottom . ') 0,(vh*' . $top . ') ' . $b_level;
                }
                // Rotate
                if (!empty($effects['r']['val']['r_dir'])) {
                    $has_lax = true;
                    $viewport = isset($effects['r']['val']['r_vp']) ? explode(',', $effects['r']['val']['r_vp']) : array(0, 100);
                    $rotates = isset($effects['r']['val']['r_num']) ? (float) $effects['r']['val']['r_num'] * 360 : 360;
                    $bottom = 1 - ( (int) $viewport[0] / 100 );
                    $top = 1 - ( (int) $viewport[1] / 100 );
                    $props['data-lax-rotate'] = $effects['r']['val']['r_dir'] === 'toleft' ? '(vh*' . $bottom . ') 0,(vh*' . $top . ') -' . $rotates : '(vh*' . $bottom . ') 0,(vh*' . $top . ') ' . $rotates;
                    if (isset($effects['r']['val']['r_origin'])) {
                        $props['data-box-position'] = self::map_transform_origin($effects['r']['val']['r_origin']);
                    }
                }
                // Scale
                if (!empty($effects['s']['val']['s_dir'])) {
                    $has_lax = true;
                    $viewport = isset($effects['s']['val']['s_vp']) ? explode(',', $effects['s']['val']['s_vp']) : array(0, 100);
                    $ratio = isset($effects['s']['val']['s_ratio']) ? (float) $effects['s']['val']['s_ratio'] : 3;
                    $bottom = 1 - ( (int) $viewport[0] / 100 );
                    $top = 1 - ( (int) $viewport[1] / 100 );
                    $props['data-lax-scale'] = $effects['s']['val']['s_dir'] === 'up' ? '(vh*' . $bottom . ') 1,(vh*' . $top . ') ' . $ratio : '(vh*' . $bottom . ') 1,(vh*' . $top . ') ' . number_format(1 / $ratio, 3);
                    if (isset($effects['s']['val']['s_origin'])) {
                        $props['data-box-position'] = self::map_transform_origin($effects['s']['val']['s_origin']);
                    }
                }
                if ($has_lax === true) {
                    $props['data-lax'] = 'true';
                }
                if ($is_lax !== 'done') {
                    $is_lax = 'done';
                    \Themify_Enqueue_Assets::addPrefetchJs(THEMIFY_URI . '/js/modules/lax.js', THEMIFY_VERSION);
                }
            }
        }
        if (isset($fields_args['custom_css_id'])) {
            $props['id'] = $fields_args['custom_css_id'];
        }
        return $props;
    }

    /**
     * Map animation speed parameter and returns new speed
     *
     * @param string $val Initial speed value
     * @param string $attr attribute name
     *
     * @return float|int Returns speed of element based on initial value
     */
    private static function map_animation_speed(float $val,string $attr = ''):float {
        if($val<0 || $val>10){
            $val=5;
        }
        if($attr === 'blur'){
            $speed = 2*$val;
        }
        else{
            $speed =  $val===3?200:($val<3?(70*$val):(670-(10-$val)*70));
        }
        return $speed;
    }

    /**
     * Map initial origin value and returns transform origin property
     *
     * @param string $props Initial origin value
     *
     * @return string Returns transform origin value of element based on initial value
     */
    private static function map_transform_origin(string $props):string {
        switch ($props) {
            case '0,0':
                $output = 'top left';
                break;

            case '50,0':
                $output = 'top center';
                break;

            case '100,0':
                $output = 'top right';
                break;

            case '0,50':
                $output = 'left center';
                break;

            case '50,50':
                $output = 'center center';
                break;

            case '100,50':
                $output = 'right center';
                break;

            case '0,100':
                $output = 'bottom left';
                break;

            case '50,100':
                $output = 'bottom center';
                break;

            case '100,100':
                $output = 'bottom right';
                break;

            default:
                $perc = \explode(',', $props);
                $output = $perc[0] . '% ' . $perc[1] . '%';
        }

        return $output;
    }

    /**
     * Return the correct animation css class name
     * @param string $effect
     * @return string
     */
    public static function parse_animation_effect($settings, array $attr = array()) {
        /* backward compatibility for addons */
        if (!is_array($settings)) {
            return '';
        }
        static $has = null;
        if ($has === null) {
            $has = \Themify_Builder_Model::is_animation_active();
        }
        if ($has !== false) {
            if (!empty($settings['hover_animation_effect'])) {
                $attr['data-tf-animation_hover'] = $settings['hover_animation_effect'];
                if (isset($attr['class'])) {
                    $attr['class'] .= ' hover-wow';
                } else {
                    $attr['class'] = 'hover-wow';
                }
                if ($has !== 'done') {
                    $has = 'load';
                }
            }
            if (!empty($settings['animation_effect'])) {
                $attr['data-tf-animation'] = $settings['animation_effect'];
                if (!in_array($settings['animation_effect'], array('fade-in', 'fly-in', 'slide-up'), true)) {
                    if (isset($attr['class'])) {
                        $attr['class'] .= ' wow';
                    } else {
                        $attr['class'] = 'wow';
                    }
                }
                if (!empty($settings['animation_effect_delay'])) {
                    $attr['data-tf-animation_delay'] = $settings['animation_effect_delay'];
                }
                if (!empty($settings['animation_effect_repeat'])) {
                    $attr['data-tf-animation_repeat'] = $settings['animation_effect_repeat'];
                }
                if ($has !== 'done') {
                    $has = 'load';
                }
            }
            if ($has === 'load') {
                $has = 'done';
                \Themify_Enqueue_Assets::preFetchAnimtion();
            }
        }
        return $attr;
    }

    /**
     * Load builder modules
     */
    public static function load_modules(string $mod_name = 'active', bool $ignore = false) {
        // load modules

        if ($mod_name !== 'active' && $mod_name !== 'all') {
            if(isset(Themify_Builder_Model::$modules[$mod_name])){//backward will be removed in the future
                return Themify_Builder_Model::$modules[$mod_name];
            }
            $className=self::get_module_class($mod_name);
            if(class_exists($className,false)){
                return $className::is_available()?$className:'';
            }
            $dir = \Themify_Builder_Model::get_modules($mod_name,$ignore);

            if($dir!==''){
                if($dir === true ){
                    $dir=THEMIFY_BUILDER_MODULES_DIR;
                }
                require $dir . '/module-' . $mod_name . '.php';
                $className=self::get_module_class($mod_name);//the second call need for backward
                return isset(Themify_Builder_Model::$modules[$mod_name])?Themify_Builder_Model::$modules[$mod_name]:($className::is_available()?$className:'');
            }
            return '';
        } 
        $modules=[];
        $items = \Themify_Builder_Model::get_modules($mod_name);
        foreach ($items as $m => $dir) {
            if ($dir === true) {
                $dir = THEMIFY_BUILDER_MODULES_DIR;
            }
            require_once $dir . '/module-' . $m . '.php';
            if(isset(Themify_Builder_Model::$modules[$m])){
                $item=Themify_Builder_Model::$modules[$m];
            }else{
                $className=self::get_module_class($m);
                if(!$className::is_available()){
                    continue;
                }
                $item=$className;
            }
            $modules[$m]=$item;
        }
        return $modules;
    }

    public static function get_modules_assets():array {
        return self::$assets;
    }

    public static function add_modules_assets($k, $item):void {
        self::$assets[$k] = $item;
    }

    public static function render(string $slug, string $mod_id, $builder_id, array &$settings = array(), bool $echo = false) {
        $template = $slug === 'highlight' || $slug === 'testimonial' || $slug === 'post' || $slug === 'portfolio' ? 'blog' : $slug;

        $vars = array(
            'module_ID' => $mod_id,
            'mod_name' => $slug,
            'builder_id' => $builder_id,
            'mod_settings' => $settings
        );
        $vars = apply_filters('themify_builder_module_render_vars', $vars, $slug);

        return self::retrieve_template('template-' . $template . '.php', $vars, '', '', $echo);
    }

    /**
     * If there's not an options tab in Themify Custom Panel meta box already defined for this post type, like "Portfolio Options", add one.
     *
     * @since 2.3.8
     *
     * @param array $meta_boxes
     *
     * @return array
     */
    public static function cpt_meta_boxes(array $meta_boxes = array()):array {
        Themify_Builder_Model::load_general_metabox(); // setup common fields

        $meta_box_id = static::SLUG . '-options';
        if (!in_array($meta_box_id, wp_list_pluck($meta_boxes, 'id'), true)) {
            $options = static::get_metabox();
            if (!empty($options)) {
                $meta_boxes = array_merge($meta_boxes, array(
                    array(
                        'name' => sprintf(__('%s Options', 'themify'), self::get_module_name()),
                        'id' => $meta_box_id,
                        'options' => $options,
                        'pages' => static::SLUG
                    )
                ));
            }
        }

        return $meta_boxes;
    }

    public static function get_module_title(array $fields_args,string $key = 'mod_title'):string {
        if (isset($fields_args[$key]) && $fields_args[$key] !== '') {
            $titleTag=apply_filters('themify_builder_module_title_tag', 'h3');
            $startTag='<'.$titleTag.' class="module-title"';
            if($key!=='' && (Themify_Builder::$frontedit_active === true || Themify_Builder_Model::is_front_builder_activate())){
                $startTag.=' data-name="'.$key.'" contenteditable="false"';
            }
            return $startTag .'>'. $fields_args[$key] . '</'.$titleTag.'>';
        }
        return '';
    }

    

    /**
     * Get query page
     */
    public static function get_paged_query():int {
        global $wp;
        if (isset($_GET['tf-page']) && is_numeric($_GET['tf-page'])) {
            $page = (int) $_GET['tf-page'];
        }
         else {
            $qpaged = get_query_var('paged');
            if (!empty($qpaged)) {
                $page = (int)$qpaged;
            }
            else {
                $qpaged = wp_parse_args($wp->matched_query);
                $page = isset($qpaged['paged']) && $qpaged['paged'] > 0?(int)$qpaged['paged']:1;
            }
        }
        return $page;
    }

    public static function query(array $args):WP_Query {
        $isPaged = isset($args['paged']) && $args['paged'] > 1;
        $hasSticky = isset($args['ignore_sticky_posts']) && $args['ignore_sticky_posts'] === false;
        $maxPage = (int) $args['posts_per_page'];
        if ($hasSticky === true) {
            $sticky_posts = get_option('sticky_posts');
            if (empty($sticky_posts)) {
                $hasSticky = false;
            } else {
                $sticky_posts = array_slice($sticky_posts, 0, $maxPage);
            }
        }
        if ($hasSticky === true && $isPaged === false) {
            $params = array(
                'post_status' => 'publish',
                'post_type' => $args['post_type'],
                'post__in' => $sticky_posts,
                'orderby' => 'post__in',
                'posts_per_page' => $maxPage,
                'ignore_sticky_posts' => true
            );
            if (isset($args['tax_query'])) {
                $params['tax_query'] = $args['tax_query'];
            }
            if (isset($args['meta_key'])) {
                $params['meta_key'] = $args['meta_key'];
            }
            if (isset($args['post__not_in'])) {
                $params['post__not_in'] = $args['post__not_in'];
            }
            $the_query = new WP_Query($params);
            if ($the_query->post_count < $maxPage) {
                if (isset($args['post__not_in'])) {
                    $args['post__not_in'] = array_merge($args['post__not_in'],$sticky_posts);;
                } else {
                    $args['post__not_in'] = $sticky_posts;
                }
                $args['ignore_sticky_posts'] = true;
                $args['posts_per_page'] = $maxPage - $the_query->post_count;
                $q = new WP_Query($args);
                $the_query->found_posts = $q->found_posts;
                $the_query->posts = array_merge($the_query->posts, $q->posts);
                $the_query->post_count = count($the_query->posts);
                $the_query->max_num_pages = ceil($the_query->found_posts / $maxPage);
                unset($q, $args);
            }
        } else {
            if ($isPaged === true && $hasSticky === true) {
                if (isset($args['post__not_in'])) {
                    $args['post__not_in']= array_merge($args['post__not_in'],$sticky_posts);
                } else {
                    $args['post__not_in'] = $sticky_posts;
                }
            }
            $the_query = new WP_Query($args);
        }

        return $the_query;
    }

    /**
     * Returns Pagination
     * @param string Markup to show before pagination links
     * @param string Markup to show after pagination links
     * @param object WordPress query object to use
     * @param original_offset number of posts configured to skip over
     * @return string
     */
    public static function get_pagination($before = '', $after = '', $query = false, $original_offset = 0, $max_page = 0, $paged = 0):string {
        if (false == $query) {
            global $wp_query;
            $query = $wp_query;
        }
        if ($paged === 0) {
            $paged = (int) self::get_paged_query();
        }
        if ($max_page === 0) {
            $numposts = $query->found_posts;
            $original_offset = (int) $original_offset;
            // $query->found_posts does not take offset into account, we need to manually adjust that
            if ($original_offset > 0) {
                $numposts -= $original_offset;
            }
            $max_page = ceil($numposts / $query->query_vars['posts_per_page']);
        }
        if ($max_page > 1) {
            Themify_Builder_Model::loadCssModules('pagenav', THEMIFY_BUILDER_CSS_MODULES . 'pagenav.css', THEMIFY_VERSION);
            if (!is_string($query) && is_single()) {
                $query = 'tf-page';
            }
        }
        return themify_get_pagenav($before, $after, $query, $max_page, $paged);
    }


    

    /**
     * Get template for module
     * @param $mod
     * @param int $builder_id
     * @param bool $echo
     * @return string
     */
    public static function template(array &$mod, $builder_id = 0,bool $echo = true) {
        if (Themify_Builder::$frontedit_active === false) {
            /* allow addons to control the display of the modules */
            $display = apply_filters('themify_builder_module_display', true, $mod, $builder_id);
            if (false === $display || ( isset($mod['mod_settings']['visibility_all']) && $mod['mod_settings']['visibility_all'] === 'hide_all' )) {
                return '';
            }
        }
        if (!isset($mod['mod_name'])) {
            return '';
        }
        $output = '';
        $slug = $mod['mod_name'];
        static $isLoaded = array();
        self::$isFirstModule = false;
        if (!isset($isLoaded[$slug])) {//load only the modules in the page
            $module = self::load_modules($slug);
            if($module===''){// check whether module active or not
                return '';
            }
            $isLoaded[$slug] = true;
            if (Themify_Builder::$frontedit_active === false && empty($mod['mod_settings']['_render_plain_content'])) {
                $assets = \is_string($module) ? $module::get_js_css() : $module->get_assets();
                static $count = 0; //only need to load the modules styles in concate for the first 2 modules to show LCP asap,even if they should load as async 
                if (!empty($assets)) {
                    $ver = isset($assets['ver']) ? $assets['ver'] : THEMIFY_VERSION;
                    self::$isFirstModule = $count < 2;
                    if (isset($assets['css']) && ($count < 2 || !isset($assets['async']))) {
                        if ($echo === true) {
                            $assets['css'] = (array) $assets['css'];
                            $content_url=content_url();
                            foreach ($assets['css'] as $k => $s) {
                                $key = is_int($k) ? $slug : $k;
                                if ($s === 1) {
                                    $s = THEMIFY_BUILDER_CSS_MODULES . $slug . '.css';
                                } else {
                                    if (\strpos($s, 'http') === false && \strpos($s,$content_url)===false) {
                                        $s = THEMIFY_BUILDER_CSS_MODULES . $s;
                                    }
                                    if (\strpos($s, '.css') === false) {
                                        $s .= '.css';
                                    }
                                }
                                \Themify_Builder_Model::loadCssModules($key, $s, $ver);
                            }
                        }
                        unset($assets['css']);
                    }
                    if (isset($assets['js']) || isset($assets['css'])) {
                        if (self::$isFirstModule === true && isset($assets['js'])) {
                            $u = $assets['js'];
                            if ($u === 1) {
                                $u = THEMIFY_BUILDER_JS_MODULES . $slug . '.js';
                            } else {
                                if (\strpos($u, 'http') === false && \strpos($s, content_url())===false) {
                                    $u = THEMIFY_BUILDER_JS_MODULES . $u;
                                }
                                if (\strpos($u, '.js') === false) {
                                    $u .= '.js';
                                }
                            }
                            \Themify_Enqueue_Assets::addPrefetchJs($u, $ver);
                        }
                        unset($assets['async']);
                        self::$assets[$slug] = $assets;
                    }
                    if ($slug === 'feature' || $slug === 'menu' || $slug === 'tab' || $slug === 'accordion') {
                        \Themify_Enqueue_Assets::addPrefetchJs(THEMIFY_BUILDER_JS_MODULES . $slug . '.js', $ver);
                    }
                    unset($assets, $ver);
                }
                ++$count;
            }
        }
        $mod['mod_settings'] = isset($mod['mod_settings']) ? $mod['mod_settings'] : array();
        if ($echo !== true) {
            $output .= PHP_EOL; // add line break
            ob_start();
        }
        do_action('themify_builder_background_styling', $builder_id, array('styling' => $mod['mod_settings'], 'mod_name' => $slug, 'element_id' => $mod['element_id']), 'module', '');
        if ($echo !== true) {
            $output .= ob_get_clean() . PHP_EOL;
        }
        elseif ($slug === 'slider' && Themify_Builder::$frontedit_active === false) {
            $isEcho = true;
            $echo = false;
        }
        // render the module
        $res = self::render($slug, 'tb_' . $mod['element_id'], $builder_id, $mod['mod_settings'], $echo);
        if (!empty($res)) {
            $output .= $res;
        }
        if ($echo === true) {
            echo $output;
        } 
        else {
            if ($slug === 'slider' && Themify_Builder::$frontedit_active === false) {
                $output = \themify_make_lazy($output, false);
                if (isset($isEcho) && $isEcho === true) {
                    echo $output;
                    return;
                }
            }
            return $output . PHP_EOL;
        }
    }

    
    /**
     * Retrieve saved settings for a module
     *
     * @return array
     */
    public static function get_element_settings( $post_id, $element_id ):array {
        $data = Themify_Builder::get_builder_modules_list( $post_id );
        if ( ! empty( $data ) ) {
            foreach ( $data as $module ) {
                if ( isset( $module['element_id'], $module['mod_settings'] ) && $module['element_id'] === $element_id ) {
                    return $module['mod_settings'];
                }
            }
        }

        return [];
    }

    /**
     * Get plain content of the module output.
     * 
     * @param array $module 
     * @return string
     */
    public static function get_static_content(array $module):string {
        if (isset($module['mod_settings'])) {
            $module['mod_settings']['_render_plain_content'] = true;
            // Remove format text filter including do_shortcode
            if (!Themify_Builder_Model::is_front_builder_activate()) {
                remove_filter('themify_builder_module_content', array('Themify_Builder_Model', 'format_text'));
            }
        } else {
            $module['mod_settings'] = array('_render_plain_content' => true);
        }
        return self::template($module, 0, false);
    }


    /**
     * Add z-index option to styling tab
     *
     * @return array
     */
    private static function add_zindex_filed(array &$styling) {//@deprecated has been moved to js
        $field = self::get_expand('zi',
                array(
                    self::get_zindex('', 'custom_parallax_scroll_zindex')
                )
        );
        if (isset($styling['type']) && 'tabs' === $styling['type']) {
            $k = key($styling['options']);
            if (isset($styling['options'][$k]['options'])) {
                $styling['options'][$k]['options'][] = $field;
            } else {
                $styling['options'][$k][] = $field;
            }
        } else {
            $styling[] = $field;
        }
        return $styling;
    }

    /**
     * Add Transform options to styling tab
     *
     * @return array
     */
    private static function add_transform_filed(array &$styling) {//@deprecated has been moved to js
        $field = self::get_expand('tr', array(
                self::get_tab(array(
                    'n' => array(self::get_transform()),
                    'h' => array(self::get_transform('', 'tr', 'h'))
                ))
        ));
        if (isset($styling['type']) && 'tabs' === $styling['type']) {
            $k = key($styling['options']);
            if (isset($styling['options'][$k]['options'])) {
                $styling['options'][$k]['options'][] = $field;
            } else {
                $styling['options'][$k][] = $field;
            }
        } else {
            $styling[] = $field;
        }
        return $styling;
    }

    public function __construct($slug) {//@deprecated 
        if (is_string($slug)) {
            $this->slug = $slug;
            $this->name = $this->get_name();
        } else {
            $this->name = $slug['name'];
            $this->slug = $slug['slug'];
            if (isset($slug['category'])) {
                $this->category = $slug['category'];
            }
        }
        Themify_Builder_Model::$modules[$this->slug] = $this;
    }

    public function get_form_settings($tab = '') {//@deprecated has been moved to js
        $styles = $this->get_styling();
        if (empty($styles)) {
            return array();
        }
        // Add Z-index to all modules
        self::add_zindex_filed($styles);
        self::add_transform_filed($styles);
        return $styles;
    }

    /**
     * Get Module Title.
     * 
     * @access public
     * @param object $module 
     */
    public function get_title($module) {//@deprecated
        return '';
    }

    public function get_assets() {//@deprecated use get_js_css
    }

    /**
     * Get module styling options.
     * 
     * @access public
     */
    public function get_styling() {//@deprecated has been moved to js
        return array();
    }

    public function get_icon() {//@deprecated use get_module_icon
        return '';
    }

    public function get_name() {//@deprecated use get_module_name
        return '';
    }

    /**
     * Get module options.
     * 
     * @access public
     */
    public function get_options() {//@deprecated has been moved to js.
        return array();
    }

    protected function _visual_template() {//@deprecated has been moved to js.
    }

    public function get_default_settings() {//@deprecated
        return false;
    }

    public function get_default_args() {//@deprecated
        return array();
    }

    public function get_live_default() {//@deprecated has been moved to js.
        return false;
    }

    public function get_visual_type() {//@deprecated has been moved to js.
        return 'live';
    }

    public function get_group() {//@deprecated has been moved to js.
        return false;
    }

    public function print_template($echo = false, $ignoreLocal = false) {//@deprecated has been moved to js
    }

    
    public static function get_element_attributes(array $props) {//@deprecated use themify_get_element_attributes
        return themify_get_element_attributes($props);
    }
    
    //add inline editing fields
    public static function add_inline_edit_fields( $name, $condition = true, $hasEditor = false, $repeat = false, $index = -1,  $echo = true) {//@deprecated
        return '';
    }



    public static function get_module_args($key = '') {//@deprecated
        return apply_filters('themify_builder_module_args', array('before_title' => '<h3 class="module-title">', 'after_title' => '</h3>'));
    }
    
    /**
     * Render a module, as a plain text
     *
     * @return string
     */
    public function get_plain_text($module) {//@deprecated
        $options = $this->get_options();
        if (empty($options)) {
            return '';
        }
        $out = array();

        foreach ($options as $field) {
            // sanitization, check for existence of needed keys
            if (!isset($field['type'], $field['id'], $module[$field['id']])) {
                continue;
            }
            // text, textarea, and wp_editor field types
            if (in_array($field['type'], array('text', 'textarea', 'wp_editor'), true)) {
                $out[] = $module[$field['id']];
            }
            // builder field type
            elseif ($field['type'] === 'builder' && is_array($module[$field['id']])) {
                // gather text field types included in the "builder" field type
                $text_fields = array();
                foreach ($field['options'] as $row_field) {
                    if (isset($row_field['type']) && in_array($row_field['type'], array('text', 'textarea', 'wp_editor'), true)) {
                        $text_fields[] = $row_field['id'];
                    }
                }
                foreach ($module[$field['id']] as $row) {
                    // separate fields from the row that have text fields
                    $texts = array_intersect_key($row, array_flip($text_fields));
                    // add them to the output
                    $out = array_merge(array_values($texts), $out);
                }
            }
        }

        return implode(' ', $out);
    }
    
    
    public static function get_pagenav($before = '', $after = '', $query = false, $original_offset = 0) {//backward compatibility for addons,deprecated use get_pagination
        return self::get_pagination($before, $after, $query, $original_offset);
    }

    public function get_plain_content($module) {//@deprecated use get_static_content
        return self::get_static_content($module);
    }

    public static function get_tab(array $options, $fullwidth = false, $cl = '') {//@deprecated has been moved to js
        $opt = array(
            'type' => 'tabs',
            'options' => $options
        );
        if ($fullwidth === true) {
            if ($cl !== '') {
                $cl .= ' tb_tabs_fullwidth';
            } else {
                $cl = 'tb_tabs_fullwidth';
            }
        }
        if ($cl !== '') {
            $opt['class'] = $cl;
        }
        return $opt;
    }

    public static function get_seperator($label = false) {//@deprecated has been moved to js
        $opt = array(
            'type' => 'separator'
        );
        if ($label !== false) {
            $opt['label'] = $label;
        }
        return $opt;
    }

    public static function get_expand($label, array $options) {//@deprecated has been moved to js
        return array(
            'type' => 'expand',
            'label' => $label,
            'options' => $options
        );
    }

    protected static function get_font_family($selector = '', $id = 'font_family', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'font_select',
            'prop' => 'font-family',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_element_font_weight($selector = '', $id = 'element_font_weight', $state = '') {//backward compatibility
    }

    protected static function get_font_size($selector = '', $id = 'font_size', $label = '', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'fontSize',
            'selector' => $selector,
            'prop' => 'font-size'
        );
        if ($label !== '') {
            $res['label'] = $label;
        }
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_line_height($selector = '', $id = 'line_height', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'lineHeight',
            'selector' => $selector,
            'prop' => 'line-height'
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_letter_spacing($selector = '', $id = 'letter_spacing', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'letterSpace',
            'selector' => $selector,
            'prop' => 'letter-spacing'
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_flex_align($selector = '', $id = 'align', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'label' => 't_a',
            'type' => 'icon_radio',
            'falign' => true,
            'prop' => 'align-content',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_flex_align_items($selector = '', $id = 'align', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'label' => 't_a',
            'type' => 'icon_radio',
            'falign' => true,
            'prop' => 'align-items',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_flex_align_content($selector = '', $id = 'align', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'label' => 't_a',
            'type' => 'icon_radio',
            'falign' => true,
            'prop' => 'align-content',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_text_align($selector = '', $id = 'text_align', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'label' => 't_a',
            'type' => 'icon_radio',
            'aligment' => true,
            'prop' => 'text-align',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_text_transform($selector = '', $id = 'text_transform', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'label' => 't_t',
            'type' => 'icon_radio',
            'text_transform' => true,
            'prop' => 'text-transform',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_text_decoration($selector = '', $id = 'text_decoration', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'icon_radio',
            'label' => 't_d',
            'text_decoration' => true,
            'prop' => 'text-decoration',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_font_style($selector = '', $id = 'font_style', $id2 = 'font_weight', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
            $id2 .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'id2' => $id2,
            'type' => 'fontStyle',
            'prop' => 'font-style',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }

        return $res;
    }

    protected static function get_color($selector = '', $id = '', $label = null, $prop = 'color', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        if ($prop === null) {
            $prop = 'color';
        }
        $color = array(
            'id' => $id,
            'type' => 'color',
            'prop' => $prop,
            'selector' => $selector
        );
        if ($label !== null) {
            $color['label'] = $label;
        }
        if ($state === 'h' || $state === 'hover') {
            $color['ishover'] = true;
        }
        return $color;
    }

    protected static function get_image($selector = '', $id = 'background_image', $colorId = 'background_color', $repeatId = 'background_repeat', $posId = 'background_position', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
            if ($colorId !== '') {
                $colorId .= '_' . $state;
            }
            if ($repeatId !== '') {
                $repeatId .= '_' . $state;
            }
            if ($posId !== '') {
                $posId .= '_' . $state;
            }
        }
        $res = array(
            'id' => $id,
            'type' => 'imageGradient',
            'prop' => 'background-image',
            'selector' => $selector,
            'origId' => $id,
            'colorId' => $colorId,
            'repeatId' => $repeatId,
            'posId' => $posId
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // CSS Filters
    protected static function get_blend($selector = '', $id = 'bl_m', $state = '', $filters_id = 'css_f') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
            $filters_id .= '_' . $state;
        }
        $res = array(
            'id' => $filters_id,
            'mid' => $id,
            'type' => 'filters',
            'prop' => 'filter',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_repeat($selector = '', $id = 'background_repeat', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'label' => 'b_r',
            'type' => 'select',
            'repeat' => true,
            'prop' => 'background-mode',
            'selector' => $selector,
            'wrap_class' => 'tb_group_element_image tb_image_options'
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_position($selector = '', $id = 'background_position', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'position_box',
            'prop' => 'background-position',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_padding($selector = '', $id = 'padding', $state = '') {//@deprecated has been moved to js
        if ($id === '') {
            $id = 'padding';
        }
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'padding',
            'prop' => 'padding',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_margin($selector = '', $id = 'margin', $state = '') {//@deprecated has been moved to js
        if ($id === '') {
            $id = 'margin';
        }
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'margin',
            'prop' => 'margin',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_gap($selector = '', $id = 'gap', $prop = 'gap', $state = '', $percent = false, $label = '') {//@deprecated has been moved to js
        if ($id === '') {
            $id = 'gap';
        }
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $units = array(
            'px' => array(
                'max' => 1000
            ),
            'em' => array(
                'max' => 50
            )
        );
        if ($percent !== false && $percent !== '') {
            $units['%'] = $percent === true ? '' : $percent;
        }
        $res = array(
            'id' => $id,
            'type' => 'range',
            'label' => $label === '' ? ($prop === 'column-gap' ? 'ng' : ($prop === 'row-gap' ? 'rg' : 'gap')) : $label,
            'prop' => $prop,
            'selector' => $selector,
            'grid_gap' => 1,
            'units' => $units
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_column_gap($selector = '', $id = 'cgap', $state = '', $percent = false, $label = '') {//@deprecated has been moved to js
        if ($id === '') {
            $id = 'cgap';
        }
        return self::get_gap($selector, $id, 'column-gap', $state, $percent, $label);
    }

    protected static function get_row_gap($selector = '', $id = 'rgap', $state = '', $percent = false, $label = '') {//@deprecated has been moved to js
        if ($id === '') {
            $id = 'rgap';
        }
        return self::get_gap($selector, $id, 'row-gap', $state, $percent, $label);
    }

    protected static function get_margin_top_bottom_opposity($selector = '', $topId = 'margin-top', $bottomId = 'margin-bottom', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $topId .= '_' . $state;
            $bottomId .= '_' . $state;
        }
        $res = array(
            'topId' => $topId,
            'bottomId' => $bottomId,
            'type' => 'margin_opposity',
            'prop' => '',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_border($selector = '', $id = 'border', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'border',
            'prop' => 'border',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_outline($selector = '', $id = 'o', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'outline',
            'prop' => 'outline',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_aspect_ratio($selector = '', $id = 'asp', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }

        $res = array(
            'id' => $id,
            'type' => 'aspectRatio',
            'prop' => 'aspect-ratio',
            'selector' => $selector,
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_multi_columns_count($selector = '', $id = 'column', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id . '_count',
            'type' => 'multiColumns',
            'prop' => 'column-count',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected static function get_color_type($selector = '', $state = '', $id = '', $solid_id = '', $gradient_id = '') {//@deprecated has been moved to js
        if ($state !== '') {
            if ($id === '') {
                $id = 'f_c_t';
            }
            if ($solid_id === '') {
                $solid_id = 'f_c';
            }
            if ($gradient_id === '') {
                $gradient_id = 'f_g_c';
            }
            $id .= '_' . $state;
            $solid_id .= '_' . $state;
            $gradient_id .= '_' . $state;
        } else {
            if ($id === '') {
                $id = 'font_color_type';
            }
            if ($solid_id === '') {
                $solid_id = 'font_color';
            }
            if ($gradient_id === '') {
                $gradient_id = 'font_gradient_color';
            }
        }

        $res = array(
            'id' => $id,
            'type' => 'fontColor',
            'selector' => $selector,
            'prop' => 'radio',
            's' => $solid_id,
            'g' => $gradient_id
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // Get Rounded Corners
    protected static function get_border_radius($selector = '', $id = 'b_ra', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'border_radius',
            'prop' => 'border-radius',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // Get Box Shadow
    protected static function get_box_shadow($selector = '', $id = 'b_sh', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'box_shadow',
            'prop' => 'box-shadow',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // Get Text Shadow
    protected static function get_text_shadow($selector = '', $id = 'text-shadow', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'text_shadow',
            'prop' => 'text-shadow',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // Get z-index
    protected static function get_zindex($selector = '', $id = 'zi') {//@deprecated has been moved to js
        return array(
            'id' => $id,
            'selector' => $selector,
            'prop' => 'z-index',
            'type' => 'zIndex'
        );
    }

    protected static function get_width($selector = '', $id = 'width', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }

        $res = array(
            'id' => $id,
            'type' => 'width',
            'prop' => 'width',
            'selector' => $selector,
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // Get Height Options plus Auto Height
    protected static function get_height($selector = '', $id = 'ht', $state = '', $minH = '', $maxH = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }

        $res = array(
            'id' => $id,
            'type' => 'height',
            'prop' => 'height',
            'selector' => $selector
        );
        if ($minH !== '') {
            $res['minid'] = $minH;
        }
        if ($maxH !== '') {
            $res['maxid'] = $maxH;
        }
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // Get CSS Position
    protected static function get_css_position($selector = '', $id = 'po', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '_' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'position',
            'prop' => 'position',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    // CSS Display
    protected static function get_display($selector = '', $id = 'disp') {//@deprecated has been moved to js
        $va_id = $id . '_va';
        $res = array();
        $res[] = array(
            'id' => $id,
            'label' => 'disp',
            'type' => 'select',
            'prop' => 'display',
            'selector' => $selector,
            'binding' => array(
                'empty' => array('hide' => $va_id),
                'block' => array('hide' => $va_id),
                'none' => array('hide' => $va_id),
                'inline-block' => array('show' => $va_id)
            ),
            'display' => true
        );
        $res[] = array(
            'id' => $va_id,
            'label' => 'valign',
            'type' => 'select',
            'prop' => 'vertical-align',
            'selector' => $selector,
            'origID' => $id,
            'va_display' => true
        );
        return $res;
    }

    // Get transform
    protected static function get_transform($selector = '', $id = 'tr', $state = '') {//@deprecated has been moved to js
        if ($state !== '') {
            $id .= '-' . $state;
        }
        $res = array(
            'id' => $id,
            'type' => 'transform',
            'prop' => 'transform',
            'selector' => $selector
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }
    
        
    protected static function get_min_width($selector = '', $id = 'mi_w') {//deprecated, included in width field
        return array();
    }

    // Get Min Height Option
    protected static function get_min_height($selector = '', $id = 'mi_h') {//deprecated, included in height field
        return array();
    }

    // Get Max Height Option
    protected static function get_max_height($selector = '', $id = 'mx_h') {//deprecated, included in height field
        return array();
    }


    protected static function get_margin_top($selector = '', $id = 'margin-top', $state = '') {//deprecated
        return array();
    }

    protected static function get_margin_bottom($selector = '', $id = 'margin-bottom', $state = '') {//deprecated
        return array();
    }

    protected static function get_multi_columns_gap($selector = '', $id = 'column', $state = '') {//backward compatibility
    }

    protected static function get_multi_columns_divider($selector = '', $id = 'column', $state = '') {//backward compatibility
    }

    protected static function get_heading_margin_multi_field($selector = '', $h_level = 'h1', $margin_side = 'top', $state = '', $id = '') {//deprecated use get_margin_top_bottom_opposity
        $id = $id === '' ? $h_level : $id;
        if ($h_level === '') {
            $h_level .= ' ';
        }
        $id = $id . '_margin_' . $margin_side;
        if ($state !== '') {
            $id .= '_' . $state;
        }
        if ($selector !== '' && is_array($selector)) {
            foreach ($selector as $key => $val) {
                $selector[$key] = $val . ' ' . $h_level;
            }
        } else {
            $selector .= ' ' . $h_level;
        }
        $res = array(
            'label' => ('top' === $margin_side ? 'm' : ''),
            'id' => $id,
            'type' => 'range',
            'prop' => 'margin-' . $margin_side,
            'selector' => $selector,
            'description' => '<span class="tb_range_after">' . sprintf(__('%s', 'themify'), $margin_side) . '</span>',
            'units' => array(
                'px' => array(
                    'min' => -1000,
                    'max' => 1000
                ),
                'em' => array(
                    'min' => -50,
                    'max' => 50
                ),
                '%' => ''
            )
        );
        if ($state === 'h' || $state === 'hover') {
            $res['ishover'] = true;
        }
        return $res;
    }

    protected function module_title_custom_style() {//@deprecated has been moved to js
        return array(
            // Background
            self::get_expand('bg', array(
                self::get_tab(array(
                    'n' => array(
                        self::get_color('.module .module-title', 'background_color_module_title', 'bg_c', 'background-color')
                    ),
                    'h' => array(
                        self::get_color('.module .module-title', 'bg_c_m_t', 'bg_c', 'background-color', 'h')
                    )
                ))
            )),
            // Font
            self::get_expand('f', array(
                self::get_tab(array(
                    'n' => array(
                        self::get_font_family('.module .module-title', 'font_family_module_title'),
                        self::get_color('.module .module-title', 'font_color_module_title'),
                        self::get_font_size('.module .module-title', 'font_size_module_title'),
                        self::get_line_height('.module .module-title', 'line_height_module_title'),
                        self::get_text_align('.module .module-title', 'text_align_module_title'),
                        self::get_text_shadow('.module .module-title', 't_sh_m_t'),
                    ),
                    'h' => array(
                        self::get_font_family('.module .module-title', 'f_f_m_t', 'h'),
                        self::get_color('.module .module-title', 'f_c_m_t', null, null, 'h'),
                        self::get_font_size('.module .module-title', 'f_s_m_t', '', 'h'),
                        self::get_text_shadow('.module .module-title', 't_sh_m_t', 'h'),
                    )
                ))
            ))
        );
    }

    /**
     * Returns a list of image/imageGradient fields in module's Styling
     */
    public static function get_styling_image_fields() : array {
        return [];
    }

	/**
	 * Return a list of module fields that need translating in WPML
	 *
	 * Format:
	 *  [
	 *      'value' => String,
	 *      'title' => Optional title displayed in WPML translation screeen
	 *      'id' => field ID,
	 *      'type' => 'TEXTAREA', 'VISUAL', 'LINK', or default: 'LINE'
	 *  ]
	 */
	public static function get_translatable_fields( $module, $classname ) : array {
		$fields = [];
		/*@note: "link" field is being registered as LINE instead, so they show up in translation editor */
		foreach ( array_merge( $classname::get_translatable_text_fields( $module ), $classname::get_translatable_link_fields( $module ) ) as $field_id ) {
			if ( isset( $module['mod_settings'][ $field_id ] ) ) {
				$fields[] = [
					'value' => $module['mod_settings'][ $field_id ],
					'id' => $field_id
				];
			}
		}
		foreach ( $classname::get_translatable_textarea_fields( $module ) as $field_id ) {
			if ( isset( $module['mod_settings'][ $field_id ] ) ) {
				$fields[] = [
					'value' => $module['mod_settings'][ $field_id ],
					'id' => $field_id,
					'type' => 'TEXTAREA'
				];
			}
		}

		foreach ( $classname::get_translatable_repeatable_fields( $module ) as $repeater_field_id => $repeater_field_childs ) {
			if ( ! empty( $module['mod_settings'][ $repeater_field_id ] ) ) {
				foreach ( $module['mod_settings'][ $repeater_field_id ] as $repeater_row_index => &$repeater_row_value ) {
					foreach ( $repeater_field_childs as $item_id => $item_type ) {
						if ( isset( $repeater_row_value[ $item_id ] ) ) {
							$fields[] = [
								'value' => $repeater_row_value[ $item_id ],
								'id' => $repeater_field_id . '::' . $repeater_row_index . '::' . $item_id,
								'type' => $item_type
							];
						}
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Translate module data
	 */
	public static function translate_module( $module, $translations ) {
		/* convert $translation array for repeatable fields */
		$m = Themify_Builder_WPML_Integration::get_module( $module['mod_name'] );
		if ( $m ) {
			$repeter_fields = $m::get_translatable_repeatable_fields( $module );
			if ( ! empty( $repeter_fields ) ) {
				foreach ( $repeter_fields as $repeater_field_id => $repeater_field_childs ) {
					foreach ( $translations as $translation_key => $translation_value ) {
						if ( str_contains( $translation_key, $repeater_field_id . '::' ) ) { // this value is for a repeatable field, need to unpack
							list( $_repeater_id, $row_id, $item_id ) = explode( '::', $translation_key );
							if ( isset( $module['mod_settings'][ $repeater_field_id ][ $row_id ][ $item_id ] ) ) {
								$module['mod_settings'][ $repeater_field_id ][ $row_id ][ $item_id ] = $translation_value;
								unset( $translations[ $translation_key ] );
							}
						}
					}
				}
			}
		}

		$module['mod_settings'] = array_merge( $module['mod_settings'], $translations );
		return $module;
	}

	public static function get_translatable_text_fields( $module ) {
		return [];
	}

	public static function get_translatable_textarea_fields( $module ) {
		return [];
	}

	public static function get_translatable_link_fields( $module ) {
		return [];
	}

	public static function get_translatable_repeatable_fields( $module ) {
		return [];
	}
}

if (!function_exists('themify_builder_testimonial_author_name')) :

    function themify_builder_testimonial_author_name($post, $show_author) {
        $out = '';
        if ('yes' === $show_author) {
            if ($author = get_post_meta($post->ID, '_testimonial_name', true))
                $out = '<span class="dash"></span><cite class="testimonial-name">' . $author . '</cite> <br/>';

            if ($position = get_post_meta($post->ID, '_testimonial_position', true))
                $out .= '<em class="testimonial-title">' . $position;

            if ($link = get_post_meta($post->ID, '_testimonial_link', true)) {
                if ($position) {
                    $out .= ', ';
                } else {
                    $out .= '<em class="testimonial-title">';
                }
                $out .= '<a href="' . esc_url($link) . '">';
            }

            if ($company = get_post_meta($post->ID, '_testimonial_company', true))
                $out .= $company;
            else
                $out .= $link;

            if ($link)
                $out .= '</a>';

            $out .= '</em>';
        }
        return $out;
    }

endif;
