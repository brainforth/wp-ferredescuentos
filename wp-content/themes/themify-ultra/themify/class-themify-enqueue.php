<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
/**
 * The file that defines css/js loading class and concate css files
 * @package Themify
 */

class Themify_Enqueue_Assets {

    public const THEMIFY_CSS_MODULES_URI=THEMIFY_URI . '/css/modules/';

    private static $wc_shortcode_type = array();
    private static $wc_data = array();
    private static $css = array('mobile_concate' => array());
    private static $done = array();
    private static $localiztion = array();
    private static $theme_css_support = array('mobile-menu'=>true,'rtl'=>true);
    private static $concateFile = null;
    private static $googleFonts = array();
    private static $guttenbergCss = array();
    public static $preLoadMedia = array();
    public static $disableGoogleFontsLoad = null;
    public static $isHeader = false;
    public static $isFooter = false;
    public static $mediaMaxWidth = 1200;
    public static $mobileMenuActive = 1100;

    public static $THEME_CSS_MODULES_URI = null;
    public static $THEME_CSS_MODULES_DIR = null;
    public static $THEME_WC_CSS_MODULES_DIR = null;
    public static $THEME_WC_CSS_MODULES_URI = null;
    public static $themeVersion = null;

    public static function init() {
        if (themify_is_themify_theme()) {
            self::$THEME_CSS_MODULES_DIR = THEME_DIR . '/styles/modules/';
            self::$THEME_CSS_MODULES_URI = THEME_URI . '/styles/modules/';

            self::$THEME_WC_CSS_MODULES_DIR = THEME_DIR . '/styles/wc/modules/';
            self::$THEME_WC_CSS_MODULES_URI = THEME_URI . '/styles/wc/modules/';

            self::$themeVersion = wp_get_theme(get_template())->display('Version');
            self::$mobileMenuActive = (int) themify_get('setting-mobile_menu_trigger_point', self::$mobileMenuActive, true);
        }
        if (!is_admin()) {
            add_action('wp_body_open', array(__CLASS__, 'body_open'), 1);
            add_filter('wp_default_scripts', array(__CLASS__, 'remove_default_js'));
            add_action('wp', array(__CLASS__, 'lazy_init'), 1);
        } else {
            add_action('wp_loaded', array(__CLASS__, 'lazy_init'), 1);
            add_action('admin_init', array(__CLASS__, 'loadMainScript'));
            add_action('admin_footer', array(__CLASS__, 'js_localize'), 18);
        }
        add_filter('themify_loops_wrapper_class', array(__CLASS__, 'load_loop_css'), 100, 6);
        add_filter('kses_allowed_protocols', array(__CLASS__, 'allow_lazy_protocols'), 100, 1);
        add_filter('sgo_js_minify_exclude', array(__CLASS__,'exclude_main_js' ),1);
        add_filter('sgo_javascript_combine_exclude', array( __CLASS__, 'exclude_main_js' ),1 );
        add_filter('autoptimize_filter_js_exclude', array(__CLASS__,'exclude_main_js' ),1);
        add_filter('autoptimize_filter_js_consider_minified',array(__CLASS__,'exclude_main_js' ),1);
        add_filter('rocket_delay_js_exclusions', array( __CLASS__, 'exclude_main_js' ),1 );
        add_filter('rocket_exclude_js',array( __CLASS__, 'exclude_main_js' ),1 );
        add_filter( 'js_do_concat', [ __CLASS__, 'Automattic_page_optimize_js_exclude' ], 10, 2 );
        add_action('pre_get_search_form', array(__CLASS__, 'load_search_form_css'), 9);
        if (!is_admin() || themify_is_ajax()) {
            add_filter('post_playlist', array(__CLASS__, 'wp_media_playlist'), 100, 3);
        }
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
        add_action('themify_cron_clear_css', array(__CLASS__, 'cron'));
        if (!wp_next_scheduled('themify_cron_clear_css')) {
            wp_schedule_event(time() + WEEK_IN_SECONDS * 4, 'four_week', 'themify_cron_clear_css');
        }
    }

    public static function lazy_init() {
        if (!is_admin() && !themify_is_login_page()) {
            remove_action('wp_head', 'wp_resource_hints', 2);
            if (self::$themeVersion !== null) {
                add_action('wp_head', array(__CLASS__, 'header_meta'),-1111);
                add_action('wp_head', array(__CLASS__, 'header_html'));
                add_filter('wp_title', array(__CLASS__, 'wp_title'), 10, 2);
                remove_action('wp_head', 'locale_stylesheet'); //remove rtl loading
            }
            add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ) );
            add_action('wp_enqueue_scripts', array(__CLASS__, 'before_enqueue'), 7);
            add_action('wp_enqueue_scripts', array(__CLASS__, 'after_enqueue'), 11);
            add_filter('style_loader_tag', array(__CLASS__, 'style_header_tag'), 10, 4);
            add_filter('render_block_data', array(__CLASS__, 'loadGuttenbergCss'), PHP_INT_MAX, 2);
            add_action('wp_head', array(__CLASS__, 'css_position'),100);
            add_action('wp_head', array(__CLASS__, 'wp_head'),-100);
            add_action('wp_footer', array(__CLASS__, 'before_footer'), -1111);
            add_action('wp_footer', array(__CLASS__, 'js_localize'), 18);
            add_action('wp_footer', array(__CLASS__, 'wp_footer'), 100);
        }
        elseif(is_admin()){
            add_action('admin_head', array(__CLASS__, 'dev_mode_styles'));
        }
        if(!is_admin() || themify_is_ajax()){
            add_filter('widget_display_callback', array(__CLASS__, 'widget_css'), 100, 3);
            add_action('wp_playlist_scripts',array(__CLASS__,'disable_playlist_template'),0,1);
        }
        add_filter('wp_audio_shortcode_library', array(__CLASS__, 'media_shortcode_library'), 100, 1);
        add_filter('wp_video_shortcode_library', array(__CLASS__, 'media_shortcode_library'), 10, 1);
        add_filter('wp_audio_shortcode', array(__CLASS__, 'audio_shortcode'), 100, 5);
        add_filter('wp_video_shortcode_override', array(__CLASS__, 'video_shortcode'), 100, 4);
        add_filter('embed_oembed_html',array(__CLASS__,'embed'),100,4);
        if (themify_is_lazyloading()) {
            themify_disable_other_lazy();
        }

        $cdn=' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com *.lottiefiles.com';
        themify_set_headers(array(
            'CONTENT-SECURITY-POLICY'=>array(
                'img-src'=>'data:',
                'style-src'=>"'unsafe-inline'".$cdn,
                'style-src-elem'=>"'unsafe-inline'".$cdn,
                'script-src'=>"'unsafe-inline' data:".$cdn,
                'script-src-elem'=>"'unsafe-inline' data:".$cdn,
                'connect-src'=>'https://themify.org *.lottiefiles.com',
                'default-src'=>'https://themify.org data:'.$cdn
            )
        ));
    }

    public static function start_buffer(){
        self::createDir();
        ob_start(array(__CLASS__, 'getBuffer'));
    }

    public static function createDir():bool {
        if (self::$concateFile === null) {
            self::$concateFile = self::getCurrentVersionFolder();
            if (!is_dir(self::$concateFile)) {
                Themify_Filesystem::mkdir(self::$concateFile,true);
                if (!Themify_Filesystem::is_dir(self::$concateFile)) {
                    clearstatcache();
                    Themify_Filesystem::mkdir(self::$concateFile,true);
                    if (!Themify_Filesystem::is_dir(self::$concateFile)) {
                        self::$concateFile = null;
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public static function remove_default_js($scripts) {
        if (!themify_builder_check('setting-jquery-migrate', 'performance-jquery_migrate') && !themify_is_login_page()) {
            $script = $scripts->registered['jquery'];
            if (!empty($script->deps)) { // Check whether the script has any dependencies
                $key = 'jquery-migrate';
                $index = isset($script->deps[1]) && $script->deps[1] === $key ? 1 : array_search($key, $script->deps, true);
                if ($index !== false) {
                    unset($script->deps[$index]);
                }
            }
        }
        return $scripts;
    }

    public static function before_enqueue() {
        self::add_css('tf_base', THEMIFY_URI . '/css/base.min.css', null, THEMIFY_VERSION);
        if (self::$themeVersion !== null) {
            self::add_css('themify_common', THEMIFY_URI . '/css/themify-common.css', null, THEMIFY_VERSION);
            if(is_admin_bar_showing()){
                self::add_css('themify_common_logged', THEMIFY_URI . '/css/themify-common-logged.css', null, THEMIFY_VERSION,'',true);
            }
        }
        self::loadMainScript();
    }

    public static function after_enqueue() {
        global $wp_styles, $wp_version;
        $css = array('wp-block-library');
        $is_theme = self::$themeVersion !== null;
        if ($is_theme === true && themify_is_woocommerce_active()) {
            add_filter('woocommerce_shortcode_products_query', array(__CLASS__, 'wc_shortcode_product'), 10, 3);
            add_action('woocommerce_before_single_product_summary', array(__CLASS__, 'wc_shortcode_product_page'));
            add_action('woocommerce_before_checkout_form_cart_notices', array(__CLASS__, 'wc_shortcode_checkout'));
            add_action('woocommerce_account_content', array(__CLASS__, 'wc_shortcode_account'));
            $wc_ver = WC()->version;
            self::$localiztion['wc_version'] = $wc_ver;
            wp_enqueue_script('wc-cart-fragments');//default load has been removed from wc 7.8.0
            if (themify_is_shop() || is_product() || (!is_checkout() && !is_account_page() && !is_checkout_pay_page() && !is_edit_account_page() && !is_order_received_page() && !is_add_payment_method_page())) {
                wp_enqueue_script('wc-add-to-cart-variation'); //load tmpl files
                wp_enqueue_script('wc-single-product');
                wp_enqueue_script('jquery-blockui');
                WC_Frontend_Scripts::localize_printed_scripts();
                global $wp_scripts;
                $js = array('js-cookie', 'wc-add-to-cart', 'wc-add-to-cart-variation', 'wc-cart-fragments', 'woocommerce', 'wc_additional_variation_images_script', 'wc-single-product');
                $arr = array();
                $disableOptimize = themify_check('setting-optimize-wc', true);
                $isDefered=themify_check('setting-jquery', true)?true:($disableOptimize?false:!themify_check('setting-defer-wc', true));
                foreach ($js as $v) {
                    if (isset($wp_scripts->registered[$v]) && wp_script_is($v)) {
                        if ($isDefered === true && !empty($wp_scripts->registered[$v]->extra['data'])) {
                            self::$wc_data[] = $wp_scripts->registered[$v]->extra['data'];
                            $wp_scripts->registered[$v]->extra['data']='';
                        }
                        if ($v === 'wc-single-product' && is_product()) {
                            continue;
                        }
                        if ($disableOptimize === false) {
                            $wp_scripts->done[] = $v;
                        }
                        $arr[$v] = $wp_scripts->registered[$v]->src;
                        if ($wc_ver !== $wp_scripts->registered[$v]->ver) {
                            $arr[$v] .= '?ver=' . $wp_scripts->registered[$v]->ver;
                        }
                    }
                }
                self::$localiztion['wc_js'] = $arr;
                if ($disableOptimize === true) {
                    self::$localiztion['wc_js_normal'] = true;
                }

                // Localize photoswipe css
                if (!empty($wp_styles->registered['photoswipe']->src)) {
                    wp_dequeue_style('photoswipe');
                    wp_dequeue_style('photoswipe-default-skin');
                    self::$localiztion['photoswipe'] = array('main' => $wp_styles->registered['photoswipe']->src, 'skin' => $wp_styles->registered['photoswipe-default-skin']->src);
                }
                $js = $arr = null;
            }
            $wc_block_prefix = 'wc-block';
            if (intval($wc_ver[0]) >= 6) {
                $wc_block_prefix .= 's';
            }
            $css[] = $wc_block_prefix . '-vendors-style';
            $css[] = $wc_block_prefix . '-style';
            $css[] = 'wp-block-library-theme';
            $css[] = 'woocommerce-layout';
            $css[] = 'woocommerce-smallscreen';
            $css[] = 'woocommerce-general';
            $css[] = 'select2';
            $css[]='woocommerce-blocktheme';
            $css[] = 'woocommerce_prettyPhoto_css';
        }
        $css = apply_filters('themify_deq_css', $css);
        foreach ($css as $v) {
            if (isset($wp_styles->registered[$v]) && wp_style_is($v)) {
                $src = $wp_styles->registered[$v]->src;
                if ($v === 'wp-block-library' || $v === 'wp-block-library-theme' || $v === $wc_block_prefix . '-style' || $v === $wc_block_prefix . '-vendors-style') {
                    if (empty($wp_styles->registered[$v]->deps) || ($v === $wc_block_prefix . '-style' && count($wp_styles->registered[$v]->deps) === 1 && $wp_styles->registered[$v]->deps[0] === $wc_block_prefix . '-vendors-style')) {
                        $wp_styles->done[] = $v;
                        self::$guttenbergCss[$v] = $src;
                    }
                    continue;
                }
                $wp_styles->done[] = $v;
                $ver = $wp_styles->registered[$v]->ver;
                if ($src[0] === '/' && $src[1] === '/') {
                    $src = (is_ssl() ? 'https:' : 'http:') . $src;
                }
                if (strpos($src, 'http') === false) {
                    $src = home_url($src);
                }
                if (empty($ver)) {
                    $ver = $wp_version;
                }
                wp_dequeue_style($v);
                self::add_css($v, $src, $wp_styles->registered[$v]->deps, $ver, $wp_styles->registered[$v]->args);
            }
        }
        $css = null;
        if ($is_theme === true) {
            if (isset(self::$theme_css_support['mobile-menu'])) {
                self::addMobileMenuCss('mobile-menu', THEME_URI . '/mobile-menu.css');
            }
            if (function_exists('themify_theme_enqueue_header')) {
                themify_theme_enqueue_header();
            }
            /* Disabel Guttenberg Global Style WP 5.9 */
            //     remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
            //  wp_dequeue_style('global-styles');
        }
    }

    public static function add_css(string $handle, string $src,?array $deps,?string $ver,?string $media = '',bool $in_footer = false) {
        if (!isset(self::$css[$handle], self::$done[$src])) {
            self::$done[$src] = true;
            if (!$media) {
                $media = 'all';
            }
            if (self::$concateFile === null || is_admin()) {
                if (strpos($handle, 'http') !== false) {
                    $handle = crc32($handle);
                }
                self::$css[$handle] = array('s' => $src, 'v' => $ver);
                if ($media !== 'all') {//need to get css files in ajax call(e.g builder live preview)
                    self::$css[$handle]['m'] = $media;
                }
                wp_enqueue_style($handle, $src, $deps, $ver, $media);
                return;
            }
            if ($in_footer === true || self::$isFooter === true) {
                if (!isset(self::$css['in_footer'])) {
                    self::$css['in_footer'] = array();
                }
                self::$css['in_footer'][$handle] = array('s' => $src, 'v' => $ver);
                if ($media !== 'all') {
                    self::$css['in_footer'][$handle]['m'] = $media;
                }
            } else {
                self::$css[$handle] = array('s' => $src, 'v' => $ver);
                if ($media !== 'all') {
                    self::$css[$handle]['m'] = $media;
                }
            }
        }
    }

    /**
     * Deregister an stylesheet
     *
     * @param string $handle
     * @param string $both
     */
    public static function remove_css(string $handle, string $type = 'both') {
        if ($handle !== 'in_footer' && self::$css !== null) {
            if ($type === 'both' || $type === 'main') {
                unset(self::$css[$handle], self::$css['in_footer'][$handle]);
            }
            if ($type === 'both' || $type === 'mobile') {
                unset(self::$css['mobile_concate'][$handle]);
            }
        }
    }

    public static function header_meta() {
        ?>
        <meta charset="<?php echo get_bloginfo('charset') ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
        <?php
    }

    public static function dev_mode_styles(){
        if ( ( themify_is_dev_mode() || Themify_Maintenance_Mode::is_enabled() ) && is_admin_bar_showing() ){
            if(is_admin()):
                ?>
                <style>
            <?php endif;?>
                /* admin bar dev mode */
        #wpadminbar #wp-toolbar .tf_admin_bar_alert .ab-item{
            padding-left:24px
        }
        .tf_admin_bar_alert>.ab-item:after{
            content:'';
            width:12px;
            height:12px;
            border-radius:100%;
            background:#ff1212;
            position:absolute;
            top:50%;
            left:6px;
            transform:translateY(-50%);
            animation:tf_bg 2s infinite alternate
        }
        #wpadminbar .tf_admin_bar_tooltip{
            width:300px;
            color:#ccc;
            background-color:rgba(51,51,51,.9);
            padding:10px 14px;
            font-size:12px;
            line-height:1.2em;
            border-radius:8px;
            margin-top:50px;
            position:absolute;
            display:none;
            top:100%;
            left:0;
            box-sizing:border-box;
            z-index:999999
        }
        #wpadminbar .tf_admin_bar_alert>.ab-item:hover .tf_admin_bar_tooltip{display:block}
        @keyframes tf_bg{0%{background:#ffb800}100%{background:#ff1212}}
        <?php  if(is_admin()):?>
            </style>
        <?php
        endif;
        }
    }

    public static function wp_head() {
        if (themify_is_lazyloading() === true) {
            $blur = (int) themify_get('setting-lazy-blur', 25, true);
            ?>
            <style id="tf_lazy_style" data-no-optimize="1">
                .tf_svg_lazy{
                    content-visibility:auto;
                    background-size:100% 25%!important;
                    background-repeat:no-repeat!important;
                    background-position:0 0, 0 33.4%,0 66.6%,0 100%!important;
                    transition:filter .3s linear!important;
                    <?php if ($blur > 0): ?>filter:blur(<?php echo $blur ?>px)!important;<?php endif; ?>
                    transform:translateZ(0)
                }
                .tf_svg_lazy_loaded{
                    filter:blur(0)!important
                }
                [data-lazy]:is(.module,.module_row:not(.tb_first)),.module[data-lazy] .ui,.module_row[data-lazy]:not(.tb_first):is(>.row_inner,.module_column[data-lazy],.module_subrow[data-lazy]){
                    background-image:none!important
                }
            </style>
            <noscript>
                <style>
                    .tf_svg_lazy{
                        display:none!important
                    }
                </style>
            </noscript>
            <?php
        }
        ?>
        <style id="tf_lazy_common" data-no-optimize="1">
            <?php if (self::$themeVersion !== null): ?>
            img{
                max-width:100%;
                height:auto
            }
            <?php endif; ?>
            <?php self::dev_mode_styles()?>
            :where(.tf_in_flx,.tf_flx){display:inline-flex;flex-wrap:wrap;place-items:center}
            .tf_fa,:is(em,i) tf-lottie{display:inline-block;vertical-align:middle}:is(em,i) tf-lottie{width:1.5em;height:1.5em}.tf_fa{width:1em;height:1em;stroke-width:0;stroke:currentColor;overflow:visible;fill:currentColor;pointer-events:none;text-rendering:optimizeSpeed;buffered-rendering:static}#tf_svg symbol{overflow:visible}:where(.tf_lazy){position:relative;visibility:visible;display:block;opacity:.3}.wow .tf_lazy:not(.tf_swiper-slide){visibility:hidden;opacity:1}div.tf_audio_lazy audio{visibility:hidden;height:0;display:inline}.mejs-container{visibility:visible}.tf_iframe_lazy{transition:opacity .3s ease-in-out;min-height:10px}:where(.tf_flx),.tf_swiper-wrapper{display:flex}.tf_swiper-slide{flex-shrink:0;opacity:0;width:100%;height:100%}.tf_swiper-wrapper>br,.tf_lazy.tf_swiper-wrapper .tf_lazy:after,.tf_lazy.tf_swiper-wrapper .tf_lazy:before{display:none}.tf_lazy:after,.tf_lazy:before{content:'';display:inline-block;position:absolute;width:10px!important;height:10px!important;margin:0 3px;top:50%!important;inset-inline:auto 50%!important;border-radius:100%;background-color:currentColor;visibility:visible;animation:tf-hrz-loader infinite .75s cubic-bezier(.2,.68,.18,1.08)}.tf_lazy:after{width:6px!important;height:6px!important;inset-inline:50% auto!important;margin-top:3px;animation-delay:-.4s}@keyframes tf-hrz-loader{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(.1);opacity:.6}}.tf_lazy_lightbox{position:fixed;background:rgba(11,11,11,.8);color:#ccc;top:0;left:0;display:flex;align-items:center;justify-content:center;z-index:999}.tf_lazy_lightbox .tf_lazy:after,.tf_lazy_lightbox .tf_lazy:before{background:#fff}.tf_vd_lazy,tf-lottie{display:flex;flex-wrap:wrap}tf-lottie{aspect-ratio:1.777}.tf_w.tf_vd_lazy video{width:100%;height:auto;position:static;object-fit:cover}
        </style>
        <?php if (self::$themeVersion !== null){
            themify_favicon_action();
        }
        self::$isHeader = true;
    }

    /**
     * Outputs Header Code
     *
     * Hooked to "wp_head"[10], load after other scripts in the header
     */
    public static function header_html(){
        echo themify_get('setting-header_html', '', true);
    }

    public static function css_position(){
        echo '<!--tf_css_position-->';//bug  in chrome https://bugs.chromium.org/p/chromium/issues/detail?id=332189x
    }

    public static function style_header_tag(string $tag, string $handle,string $href,string $media):string {
        $src = strtok($href, '?');
        unset(self::$preLoadMedia[$src]);
        $preload = '<link rel="preload" href="' . $href . '" as="style"';
        if ($media !== 'all' && $media) {
            $preload .= ' media="' . $media . '"';
        }
        $preload .= '>' . $tag;
        return $preload;
    }

    /**
     * Set a default title for the front page
     *
     * @return string
     * @since 1.7.6
     */
    public static function wp_title(string $title,string $sep):string {
        if (empty($title) && ( is_home() || is_front_page() )) {
            global $aioseop_options;
            return !empty($aioseop_options) && class_exists('All_in_One_SEO_Pack',false) ? $aioseop_options['aiosp_home_title'] : get_bloginfo('name');
        }
        return str_replace($sep, '', $title);
    }

    public static function wc_shortcode_product($query_args, $attr, $type) {
        if ($type === 'product') {
            self::$wc_shortcode_type[$type] = true;
        }
        return $query_args;
    }

    public static function wc_shortcode_product_page() {
        self::$wc_shortcode_type['product'] = true;
    }

    public static function wc_shortcode_checkout() {
        self::$wc_shortcode_type['checkout'] = true;
    }

    public static function wc_shortcode_account($msg = '') {
        self::$wc_shortcode_type['account'] = true;
        return $msg;
    }

    public static function js_localize() {
        
        //remove_action('wp_footer', array(__CLASS__, 'js_localize'), 18);
        self::localize_script('themify-main-script', 'themify_vars', apply_filters('themify_main_script_vars', self::$localiztion));
        if(!is_admin()){
            global $wp_scripts;
            $inline_js=$wp_scripts->registered['themify-main-script']->extra['data'];
            if(!empty(self::$wc_data)){
                $inline_js.=implode('',self::$wc_data);
            }
            ?>
            <!--googleoff:all-->
            <!--noindex-->
            <!--noptimize-->
            <script id="tf_vars" data-no-optimize="1" data-noptimize="1" data-no-minify="1" data-cfasync="false" defer="defer" src="data:text/javascript;base64,<?php echo base64_encode($inline_js)?>"></script>
            <!--/noptimize-->
            <!--/noindex-->
            <!--googleon:all-->
            <?php
            $wp_scripts->registered['themify-main-script']->extra['data'] = $inline_js=self::$wc_data=null;
            do_action('tf_load_styles');
            self::$isFooter = true;
        }
        else {
            self::loadIcons();
        }
        self::$localiztion = array();
    }

    /**
     * Copy of WP_Scripts::localize() except it uses JSON_UNESCAPED_SLASHES
     *
     * @documented in wp-includes/class.wp.scripts.php
     */
    public static function localize_script(string $handle,string $object_name,array $l10n) {
        global $wp_scripts;

        if ('jquery' === $handle) {
            $handle = 'jquery-core';
        }

        if (is_array($l10n) && isset($l10n['l10n_print_after'])) { // back compat, preserve the code in 'l10n_print_after' if present.
            $after = $l10n['l10n_print_after'];
            unset($l10n['l10n_print_after']);
        }

        foreach ((array) $l10n as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $l10n[$key] = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        }

        $script = "var $object_name = " . wp_json_encode($l10n, JSON_UNESCAPED_SLASHES) . ';';

        if (!empty($after)) {
            $script .= "\n$after;";
        }

        $data = $wp_scripts->get_data($handle, 'data');

        if (!empty($data)) {
            $script = "$data\n$script";
        }

        return $wp_scripts->add_data($handle, 'data', $script);
    }

    public static function before_footer() {
        if (themify_is_woocommerce_active()) {
            remove_action('wp_footer', 'wc_no_js');
        }
        if (self::$themeVersion !== null && !is_admin()) {
            self::add_css('theme-style', THEME_URI . '/style.css', null, self::$themeVersion);
            if (self::$mediaMaxWidth !== false) {
                self::add_css('themify-media-queries', THEME_URI . '/media-queries.css', null, self::$themeVersion, '(max-width:' . self::$mediaMaxWidth . 'px)');
            }
            if (isset(self::$theme_css_support['wc'])) {
                self::add_css('tf_theme_wc', THEME_URI . '/styles/wc/woocommerce.css', null, self::$themeVersion);
                if (isset(self::$theme_css_support['wc_single_product']) && (isset(self::$wc_shortcode_type['product']) || is_product())) {
                    self::loadThemeWCStyleModule('single/product');
                }
                if ((isset(self::$theme_css_support['wc_account']) || isset(self::$theme_css_support['wc_register_form'])) && (isset(self::$wc_shortcode_type['account']) || is_account_page())) {
                    if (is_user_logged_in()) {
                        if(isset(self::$theme_css_support['wc_account'])){
                            self::loadThemeWCStyleModule('pages/account');
                        }
                    } elseif(isset(self::$theme_css_support['wc_register_form'])) {
                        self::loadThemeWCStyleModule('pages/register-form');
                    }
                }
                if (isset(self::$theme_css_support['wc_checkout']) && (isset(self::$wc_shortcode_type['checkout']) || is_checkout())) {
                    self::loadThemeWCStyleModule('pages/checkout');
                }
                if (isset(self::$theme_css_support['wc_cart']) && is_cart()) {
                    self::loadThemeWCStyleModule('pages/cart');
                }
            }
            self::$wc_shortcode_type = null;
            if (function_exists('themify_theme_enqueue_footer')) {
                themify_theme_enqueue_footer();
            }
            if (is_rtl() && isset(self::$theme_css_support['rtl'])) {
                self::add_css('theme-style-rtl', THEME_URI . '/rtl.css', null, self::$themeVersion);
            }
            themify_enqueue_framework_assets();
            // Themify child base styling
            if (is_child_theme()) {
                $modified = filemtime(get_stylesheet_directory() . '/style.css');
                if ($modified === false) {
                    $modified = '';
                }
                self::add_css('theme-style-child', get_stylesheet_uri(), null, self::$themeVersion . $modified);
            }
            // User stylesheet
            $custom_css = get_template_directory() . '/custom_style.css';
            if (is_file($custom_css)) {
                $modified = filemtime($custom_css);
                if ($modified === false) {
                    $modified = '';
                }
                self::add_css('custom-style', THEME_URI . '/custom_style.css', null, THEMIFY_VERSION . $modified);
            }
            unset($custom_css);
            if (is_admin_bar_showing() && is_file(self::$THEME_CSS_MODULES_DIR . 'admin-bar.css')) {
                self::loadThemeStyleModule('admin-bar', false, true);
            }
            if (class_exists('Themify_Builder',false) &&  Themify_Builder_Model::is_front_builder_activate() === true && is_file(self::$THEME_CSS_MODULES_DIR . 'builder-active.css')) {
                self::loadThemeStyleModule('builder-active', false, true);
            }
        }
    }

    public static function wp_footer() {
        if (!empty(self::$css['in_footer'])) {
            foreach (self::$css['in_footer'] as $k => $v) {
                $m = isset($v['m']) ? ' media="' . $v['m'] . '"' : '';
                $href = $v['s'];
                if (!empty($v['v'])) {
                    $href .= strpos($href, '?') === false ? '?' : '&';
                    $href .= 'ver=' . $v['v'];
                }
                ?>
                <link rel="preload" href="<?php echo $href ?>" as="style"<?php echo $m ?>><link id="<?php echo $k ?>-css" rel="stylesheet" href="<?php echo $href ?>"<?php echo $m ?>>
                <?php
            }
        }
        if (self::$themeVersion !== null) {
            echo "\n\n", themify_get('setting-footer_html', '', true);
        }
        add_action('shutdown',array(__CLASS__,'buffer_end'),-99999999);
    }

    public static function buffer_end():void{
        ob_end_flush();
    }

    private static function getBuffer(string $body):string{
        $key = '';
        $exist = $hasFonts = false;
        self::$css = apply_filters('themify_main_concate', self::$css);
        self::$css['mobile_concate'] = apply_filters('themify_mobile_concate', self::$css['mobile_concate']);
        foreach (self::$css as $k => $v) {
            if ($k !== 'in_footer' && $k !== 'mobile_concate') {
                $key .= $k . $v['v'];
            }
        }
        $output = '';
        if ($key !== '') {
            if (isset(self::$css['woocommerce-general']) && strpos($body, 'star-rating') !== false) {
                self::addPreLoadMedia(dirname(self::$css['woocommerce-general']['s'],2) . '/fonts/star.woff', 'preload', 'font', null, null, 'high');
            }
            $isDevMode=themify_is_dev_mode() && (!class_exists('Themify_Builder_Model',false) || !Themify_Builder_Model::is_front_builder_activate());
            if(self::$concateFile===null){
                $isDevMode=true;
                $exist=false;
                $key='';
            }
            else{
                $key .= implode('', array_keys(self::$css['mobile_concate']));
                $key = crc32($key);
                self::$concateFile .= 'themify-' . $key . '.css';
                $exist = Themify_Filesystem::is_file(self::$concateFile);
                $regenerate = false;
                if ($exist === true) {
                    $regenerate = !apply_filters('themify_concate_css', !$isDevMode, self::$concateFile); //opposite logic for backward compatibility
                    $isDeleted = Themify_Filesystem::is_file(self::$concateFile . 'del');
                    $regenerate = $regenerate === true || $isDeleted === true;
                    if ($regenerate === true) {
                        if ($isDeleted === true) {
                            Themify_Filesystem::delete(self::$concateFile . 'del','f');
                        }
                        $exist = false;
                    }
                    unset($isDeleted);
                }
            }
            if ($exist === false) {
                if (!isset($str)) {
                    $str = '';
                }
                // Add theme and fw version
                $theme_name = '';
                $replace = array(THEMIFY_URI, home_url());
                if (self::$themeVersion !== null) {
                    $theme = wp_get_theme();
                    $theme_name = (is_child_theme() ? $theme->parent()->Name : $theme->display('Name')) . ' ' . self::$themeVersion . ' ';
                    $replace[] = THEME_URI;
                    $theme = null;
                }
                $str = PHP_EOL . '/* ' . $theme_name . 'framework ' . THEMIFY_VERSION . ' */' . $str . PHP_EOL;
                $str = '@charset "UTF-8";' . $str;
                unset($theme_name);
                if($isDevMode===true && self::$themeVersion!==null && (self::$concateFile===null || themify_is_concate_disabled())){
                    $key='';
                }
                foreach (self::$css as $k => $v) {
                    if ($k !== 'in_footer' && $k !== 'mobile_concate') {
                        $content = $key !== ''? Themify_Filesystem::get_file_content($v['s']) : null;
                        if (!empty($content)) {
                            $dir = dirname($v['s']);
                            $content = strtr($content,
                                array(
                                    '@charset "UTF-8";'=>'',
                                    '..'=>dirname($dir),
                                    "url('fonts/"=>"url({'$dir}/fonts/",
                                    "url('images/"=>"url('{$dir}/images/",
                                    "url(fonts/"=>"url({$dir}/fonts/",
                                    "url(images/"=>"url({$dir}/images/"
                                ));
                            if ($k === 'woocommerce-general') {
                                $content = str_replace('@font-face{', '@font-face{font-display:swap;', $content);
                            }
                            if (isset($v['m'])) {
                                $content = '@media ' . $v['m'] . '{' . PHP_EOL . $content . PHP_EOL . '}';
                            }
                            $str .= PHP_EOL . '/*' . str_replace($replace, '', $v['s']) . '*/' . PHP_EOL . $content;
                        }
                        elseif (!Themify_Filesystem::get_file_content($v['s'], true)) {
                            $key = $str = '';
                        }
                        if (isset(self::$preLoadMedia[$k])) {
                            unset(self::$preLoadMedia[$k]);
                        }
                        $media = isset($v['m'])?' media="'.$v['m'].'"':'';
                        $output .= '<link rel="preload" href="' . $v['s'] . '?ver=' . $v['v'] . '" as="style"'.$media.'>' . "\n" . '<link id="' . $k . '-css" rel="stylesheet" href="' . $v['s'] . '?ver=' . $v['v'] . '"'.$media.'>' . "\n";
                    }
                }
                unset($content);
                if (!empty(self::$css['mobile_concate'])) {
                    $media = 'screen and (max-width:' . self::$mobileMenuActive . 'px)';
                    $mobileStr = PHP_EOL . '/* START MOBILE MENU CSS */' . PHP_EOL . '@media ' . $media . '{';
                    foreach (self::$css['mobile_concate'] as $k => $v) {
                        $content = $key !== '' ? Themify_Filesystem::get_file_content($v) : null;
                        if (!empty($content)) {
                            $mobileStr .= PHP_EOL . '/*' . str_replace($replace, '', $v) . '*/' . PHP_EOL . trim($content);
                        } else {
                            $mobileStr = $key = '';
                        }
                        if (isset(self::$preLoadMedia[$k])) {
                            unset(self::$preLoadMedia[$k]);
                        }
                        $output .= '<link rel="preload" href="' . $v . '?ver=' . self::$themeVersion . '" media="' . $media . '" as="style"><link id="tf-mobile-' . $k . '-css" rel="stylesheet" href="' . $v . '?ver=' . self::$themeVersion . '" media="' . $media . '">';
                    }
                    unset(self::$css['mobile_concate'], $content);
                    if ($mobileStr !== '') {
                        $mobileStr .= PHP_EOL . '}' . PHP_EOL . '/* END MOBILE MENU CSS */';
                        $str .= $mobileStr;
                        unset($mobileStr);
                    }
                }
                if ($key !== '' && (empty(self::$concateFile) || (($regenerate === true || !Themify_Filesystem::is_file(self::$concateFile))))) {
                    if(!file_put_contents(self::$concateFile.'tmp', $str) || Themify_Filesystem::rename(self::$concateFile.'tmp',self::$concateFile)===false){//tmp file need because file_put_contents isn't atomic(another process can read not ready file),locking file(LOCK_EX) is slow,that is why we are using rename(it is atomic)
                        $key = '';
                        Themify_Filesystem::delete(self::$concateFile.'tmp','f');
                    }
                }
                unset($str, $replace);
            }
        }
        if ( true === apply_filters( 'themify_enable_lazyload', true ) ) {
            $body = themify_make_lazy($body);
        }
        $path = self::getPreLoad();
        if (self::$disableGoogleFontsLoad === null) {
            $path.= self::loadGoogleFonts();
        }
        if ($key !== '') {
            $upload_dir = themify_upload_dir();
            $href = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], self::$concateFile);
            unset($upload_dir);
            $path .= '<link rel="preload" fetchpriority="high" href="' . $href . '" as="style"><link fetchpriority="high" id="themify_concate-css" rel="stylesheet" href="' . $href . '">';
        } else {
            $path .= $output.'<style id="themify_concate-css"></style>';//need when dev mode is enabled
        }
        unset($output);
        self::$concateFile =  null;
        if (strpos($body, 'fonts.googleapis.com') !== false) {
            $path .= '<link rel="dns-prefetch" href="https://fonts.gstatic.com">';
        }
        if (strpos($body, '//www.youtube.com/embed') !== false) {
            $path .= '<link rel="preconnect" href="https://www.youtube.com">';
        }
        if (strpos($body, '//player.vimeo.com/video') !== false) {
            $path .= '<link rel="preconnect" href="https://player.vimeo.com">';
        }
        if (themify_get('setting-ga_m_id', '', true) !== '' || strpos($body, 'googletagmanager.com') !== false) {
            $path .= '<link rel="preconnect" href="https://www.google-analytics.com">';
        }
        if (self::$themeVersion !== null && ($custom_css = themify_get('setting-custom_css', false, true))) {
            $path.='<!--custom css:start--><style>'. $custom_css.'</style><!--custom css:end-->';
        }
        self::$css = self::$googleFonts = self::$preLoadMedia = $rep=array();
        if(strpos($body,'<!--tf_svg_holder-->')!==false){
            $rep['<!--tf_svg_holder-->']=self::loadIcons(false);
        }
        else{
            $rep['</body>']=self::loadIcons(false).'</body>';
        }
        if(strpos($body,'<!--tf_css_position-->')!==false){
            $rep['<!--tf_css_position-->']=$path;
        }
        else{
            $rep['</head>']=$path.'</head>';
        }
        return strtr($body,$rep);
    }

    public static function loadIcons(bool $echo = true) {
        $fonts = Themify_Icon_Font::get_used_icons();
        $svg = '<svg id="tf_svg" style="display:none"><defs>';
        if (!empty($fonts)) {
            $st = '';

            foreach ($fonts as $k => $v) {
                $w = isset($v['vw']) ? $v['vw'] : '32';
                $h = isset($v['vh']) ? $v['vh'] : '32';
                $p = isset($v['is_fontello']) ? ' transform="matrix(1 0 0 -1 0 ' . $h . ')"' : '';
                $svg .= '<symbol id="tf-' . $k . '" viewBox="0 0 ' . $w . ' ' . $h . '"><path d="' . $v['p'] . '"' . $p . '/></symbol>';
                if (isset($v['w'])) {
                    $st .= '.tf_fa.tf-' . $k . '{width:' . $v['w'] . 'em}';
                }
            }
            if ($st !== '') {
                $svg .= '<style id="tf_fonts_style">' . $st . '</style>';
                $st = null;
            }
        }
        $svg .= '</defs></svg>';
        $fonts = null;
        if ($echo === false) {
            return $svg;
        }
        echo $svg;
    }

    private static function get_webp_support():string {
        return PHP_EOL . '#BEGIN_WEBP_OUTPUT_BY_THEMIFY
        <IfModule mod_rewrite.c>
            RewriteEngine On
            # serves a .webp image instead of jpg/png
            RewriteCond %{HTTP_ACCEPT} image/webp
            RewriteCond %{REQUEST_FILENAME} ^(.+)\.(jpe?g|jpg|png|gif)$
            RewriteCond %1\.webp -f
            RewriteRule ^(.+)\.(jpe?g|jpg|png|gif)$ $1.webp [T=image/webp,E=accept:1]
        </IfModule>
        <IfModule mod_headers.c>
          Header append Vary Accept env=REQUEST_image
        </IfModule>
        <IfModule mod_mime.c>
          AddType image/webp .webp
        </IfModule>
        #END_WEBP_OUTPUT_BY_THEMIFY
        ' . PHP_EOL;
    }

    private static function get_gzip_htaccess():string {
        return PHP_EOL . '#BEGIN_GZIP_OUTPUT_BY_THEMIFY
        <IfModule mod_rewrite.c>
            <Files *.js.gz>
            AddType "text/javascript" .gz
            AddEncoding gzip .gz
            </Files>
            <Files *.css.gz>
            AddType "text/css" .gz
            AddEncoding gzip .gz
            </Files>
            <Files *.svg.gz>
            AddType "image/svg+xml" .gz
            AddEncoding gzip .gz
            </Files>
            <Files *.json.gz>
            AddType "application/json" .gz
            AddEncoding gzip .gz
            </Files>
            # Serve pre-compressed gzip assets
            RewriteCond %{HTTP:Accept-Encoding} gzip
            RewriteCond %{REQUEST_FILENAME}.gz -f
            RewriteRule ^(.*)$ $1.gz [QSA,L]
        </IfModule>
        #END_GZIP_OUTPUT_BY_THEMIFY
        ' . PHP_EOL;
    }

    private static function get_mod_rewrite():string {
        return PHP_EOL . '#BEGIN_GZIP_COMPRESSION_BY_THEMIFY
                <IfModule mod_deflate.c>
            #add content typing
            AddType application/x-gzip .gz .tgz
            AddEncoding x-gzip .gz .tgz
            # Insert filters
            AddOutputFilterByType DEFLATE text/plain
            AddOutputFilterByType DEFLATE text/html
            AddOutputFilterByType DEFLATE text/xml
            AddOutputFilterByType DEFLATE text/css
            AddOutputFilterByType DEFLATE application/xml
            AddOutputFilterByType DEFLATE application/xhtml+xml
            AddOutputFilterByType DEFLATE application/rss+xml
            AddOutputFilterByType DEFLATE application/javascript
            AddOutputFilterByType DEFLATE application/x-javascript
            AddOutputFilterByType DEFLATE application/x-httpd-php
            AddOutputFilterByType DEFLATE application/x-httpd-fastphp
            AddOutputFilterByType DEFLATE image/svg+xml
            AddOutputFilterByType DEFLATE image/svg
            <IfModule mod_headers.c>
                # Make sure proxies don\'t deliver the wrong content
                Header append Vary User-Agent env=!dont-vary
            </IfModule>
        </IfModule>
                # END GZIP COMPRESSION
        ## EXPIRES CACHING ##
        <IfModule mod_expires.c>
            ExpiresActive On
            ExpiresByType image/jpg "access plus 1 year"
            ExpiresByType image/jpeg "access plus 1 year"
            ExpiresByType image/gif "access plus 1 year"
            ExpiresByType image/png "access plus 1 year"
            ExpiresByType image/webp "access plus 1 year"
            ExpiresByType image/apng "access plus 1 year"
            ExpiresByType image/svg+xml "access plus 1 year"
            ExpiresByType image/svg "access plus 1 year"
            ExpiresByType image/ico "access plus 1 year"
            ExpiresByType image/x-icon "access plus 1 year"
            ExpiresByType application/gzip "access plus 1 year"
            ExpiresByType text/css "access plus 1 year"
            ExpiresByType text/x-component "access plus 1 year"
            ExpiresByType text/javascript "access plus 1 year"
            ExpiresByType text/x-javascript "access plus 1 year"
            ExpiresByType application/pdf "access plus 1 month"
            ExpiresByType application/javascript "access plus 1 year"
            ExpiresByType application/x-javascript "access plus 1 year"
            ExpiresByType application/json "access plus 1 year"
            ExpiresByType application/ld+json "access plus 1 year"
            ExpiresByType application/xml "access plus 0 seconds"
            ExpiresByType text/xml "access plus 0 seconds"
            ExpiresByType application/x-web-app-manifest+json "access plus 0 seconds"
            ExpiresByType text/cache-manifest "access plus 0 seconds"
            ExpiresByType audio/ogg "access plus 4 months"
            ExpiresByType audio/mp3 "access plus 4 months"
            ExpiresByType video/mp4 "access plus 4 months"
            ExpiresByType video/ogg "access plus 4 months"
            ExpiresByType video/webm "access plus 4 months"
            ExpiresByType application/atom+xml "access plus 1 day"
            ExpiresByType application/rss+xml "access plus 1 day"
            ExpiresByType application/font-woff "access plus 1 year"
            ExpiresByType application/vnd.ms-fontobject "access plus 1 year"
            ExpiresByType application/x-font-ttf "access plus 1 year"
            ExpiresByType font/opentype "access plus 1 year"
            ExpiresByType font/woff "access plus 1 year"
            ExpiresByType font/woff2 "access plus 1 year"
            ExpiresByType font/application/x-font-woff2 "access plus 1 year"
            ExpiresByType application/font-woff2 "access plus 1 year"
        </IfModule>
        #Alternative caching using Apache`s "mod_headers", if it`s installed.
        #Caching of common files - ENABLED
        <IfModule mod_headers.c>
            <FilesMatch "\.(pdf|xls|rar|zip|tgz|tar|html|txt)$">
                Header set Cache-Control "max-age=2628000, public"
            </FilesMatch>
            <FilesMatch "\.(jpg|jpeg|gif|png|webp|apng|svg|js|mjs|css|mp3|ogg|mpe?g|avi|gz|woff|woff2|eot|ttf|mp4|doc|ico|ogv|svgz|otf|rss|ppt|mid|midi|wav|bmp|rtf|json|jsonld)$">
                Header set Cache-Control "max-age=31536000, public"
            </FilesMatch>
            # Set Keep Alive Header
            Header set Connection keep-alive
        </IfModule>

        <IfModule mod_gzip.c>
          mod_gzip_on Yes
          mod_gzip_dechunk Yes
          mod_gzip_item_include file \.(html?|txt|css|js|php|pl)$
          mod_gzip_item_include handler ^cgi-script$
          mod_gzip_item_include mime ^text/.*
          mod_gzip_item_include mime ^application/x-javascript.*
          mod_gzip_item_exclude mime ^image/.*
          mod_gzip_item_exclude rspheader ^Content-Encoding:.*gzip.*
        </IfModule>

        # If your server don`t support ETags deactivate with "None" (and remove header)
        <IfModule mod_expires.c>
          <IfModule mod_headers.c>
            Header unset ETag
          </IfModule>
          FileETag None
        </IfModule>
        ## EXPIRES CACHING ##
        #END_GZIP_COMPRESSION_BY_THEMIFY' . PHP_EOL;
    }

    public static function rewrite_htaccess($gzip = false, $webp = false,$browser=false) {
        $htaccess_file = self::getHtaccessFile();
        if (is_file($htaccess_file) && Themify_Filesystem::is_writable($htaccess_file)) {
            if (themify_get_server() === 'iis') {//for iis we need to add webp mimeType
                $iis_config = get_home_path() . 'web.config';
                if (is_file($iis_config) && Themify_Filesystem::is_writable($iis_config)) {
                    $rules = trim(Themify_Filesystem::get_contents($iis_config));
                    if (!empty($rules) && strpos($rules, 'mimeType="image/webp"') === false) {
                        $replace = '<!--BEGIN_WEBP_OUTPUT_BY_THEMIFY-->
                        <mimeMap fileExtension=".webp" mimeType="image/webp"/>
                        <!--END_WEBP_OUTPUT_BY_THEMIFY-->';
                        if (preg_match_all('#\<staticContent\>#', $rules) > 0) {
                            $rules = preg_replace('#\<staticContent\>#', '<staticContent>' . $replace, $rules, 1);
                        } else {
                            $rules = preg_replace('#\<rewrite\>#', '<staticContent>' . $replace . '</staticContent><rewrite>', $rules, 1);
                        }
                        unset($replace);
                        Themify_Filesystem::put_contents($iis_config, trim($rules));
                    }
                }
                unset($iis_config);
            }
            $rules = trim(Themify_Filesystem::get_contents($htaccess_file));
            $startOutputTag = '#BEGIN_GZIP_OUTPUT_BY_THEMIFY';
            $endOutputTag = '#END_GZIP_OUTPUT_BY_THEMIFY';

            $startGzipTag = '#BEGIN_GZIP_COMPRESSION_BY_THEMIFY';
            $endGzipTag = '#END_GZIP_COMPRESSION_BY_THEMIFY';

            $startWebTag = '#BEGIN_WEBP_OUTPUT_BY_THEMIFY';
            $endWebTag = '#END_WEBP_OUTPUT_BY_THEMIFY';
            $hasChange = false;

            if ($webp === false) {
                if (strpos($rules, $startWebTag) === false) {
                    $rules = self::get_webp_support() . $rules;
                    $hasChange = true;
                }
            } elseif (strpos($rules, $startWebTag) !== false) {
                $startsAt = strpos($rules, $startWebTag);
                $endsAt = strpos($rules, $endWebTag, $startsAt);
                $textToDelete = substr($rules, $startsAt, ($endsAt + strlen($endWebTag)) - $startsAt);
                $rules = str_replace($textToDelete, '', $rules);
                $hasChange = true;
            }

            if ($gzip === false) {
                if (strpos($rules, $startOutputTag) === false) {
                    $rules = self::get_gzip_htaccess() . $rules;
                    $hasChange = true;
                }
            }
            elseif (strpos($rules, $startOutputTag) !== false) {
                $startsAt = strpos($rules, $startOutputTag);
                $endsAt = strpos($rules, $endOutputTag, $startsAt);
                $textToDelete = substr($rules, $startsAt, ($endsAt + strlen($endOutputTag)) - $startsAt);
                $rules = str_replace($textToDelete, '', $rules);
                $hasChange = true;
            }

            if($browser===false){
                if (strpos($rules, 'mod_deflate.c') === false && strpos($rules, 'mod_gzip.c') === false) {
                    $rules .= self::get_mod_rewrite();
                    $hasChange = true;
                }
            }
            elseif (strpos($rules, $startGzipTag) !== false) {
                $startsAt = strpos($rules, $startGzipTag);
                $endsAt = strpos($rules, $endGzipTag, $startsAt);
                $textToDelete = substr($rules, $startsAt, ($endsAt + strlen($endGzipTag)) - $startsAt);
                $rules = str_replace($textToDelete, '', $rules);
                $hasChange = true;
            }

            if ($hasChange === true) {
                return Themify_Filesystem::put_contents($htaccess_file, trim($rules));
            }
        }
    }

    public static function getHtaccessFile():string{
        $f=get_home_path() . '.htaccess';
        if(!is_file($f)){
            $f=ABSPATH . '.htaccess';
        }
        return $f;
    }

    public static function addCssToFile(string $handle, string $src,?string $ver = THEMIFY_VERSION, $position = false):bool {
        if (self::$concateFile === null) {
            return false;
        }
        if (!isset(self::$css[$handle])) {
            if ($position === false) {
                self::$css[$handle] = array('s' => $src, 'v' => $ver);
            } elseif (isset(self::$css[$position])) {
                $keys = array_keys(self::$css);
                $index = array_search($position, $keys) + 1;
                self::$css = array_slice(self::$css, 0, $index) + array($handle => array('s' => $src, 'v' => $ver)) + array_slice(self::$css, $index);
            } else {
                return false;
            }
        }
        return true;
    }

    public static function addPreLoadJs(string $src,string $ver = THEMIFY_VERSION, string $importance = 'low'):array {
        return self::addPreLoadMedia($src, 'preload', 'script', $ver, null, $importance);
    }

    public static function addPrefetchJs(string $src, string $ver = THEMIFY_VERSION, string $importance = 'low'):array {
        return self::addPreLoadMedia($src, 'prefetch', 'script', $ver, null, $importance);
    }

    public static function addPreLoadCss(string $src, string $ver = THEMIFY_VERSION, $m = 'all', string $importance = ''):array {
        return self::addPreLoadMedia($src, 'preload', 'style', $ver, $m, $importance);
    }

    public static function addPrefetchCss(string $src,string $ver = THEMIFY_VERSION, $m = 'all', string $importance = ''):array {
        return self::addPreLoadMedia($src, 'prefetch', 'style', $ver, $m, $importance);
    }

    public static function addPreLoadMedia(string $src, string $rel = 'preload', string $type = 'image', $ver = '', $m = 'all', string $importance = ''):array {

        if(!isset(self::$done[$src]) && ($rel!=='prefetch' || !isset(self::$preLoadMedia[$src]))){
            self::$preLoadMedia[$src] = array('t' => $type, 'r' => $rel);
            if ($type !== 'image' && $type !== 'font') {
                if ($type === 'style') {
                    self::$preLoadMedia[$src]['m'] = $m;
                }
                if ($ver !== '') {
                    self::$preLoadMedia[$src]['v'] = $ver;
                }
            }
            elseif($type==='image' && $ver!=='' && $ver!==null && $m!=='' && $m!==null && $m!=='all'){//ver is srcset, m sizes for image
                self::$preLoadMedia[$src]['srcset'] = $ver;
                self::$preLoadMedia[$src]['sizes'] = $m;
            }
            if ($importance !== '') {
                self::$preLoadMedia[$src]['i'] = $importance;
            }
        }
        return array('s'=>$src,'v'=>isset(self::$preLoadMedia[$src]['v'])?self::$preLoadMedia[$src]['v']:null);
    }

    public static function add_js($handle, $src, $deps, $ver, $in_footer = true) {
        wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
    }

    public static function getKnownJs():array {
        static $arr = null;
        if ($arr === null) {
            $arr = array();
            if (!is_admin()) {
                $isDefered=themify_check('setting-jquery', true)?true:(themify_check('setting-optimize-wc', true)?false:!themify_check('setting-defer-wc', true));
                if ($isDefered===true && themify_is_woocommerce_active()) {
                    $arr = array('flexslider', 'wc-single-product', 'woocommerce', 'zoom', 'js-cookie', 'jquery-blockui', 'jquery-cookie', 'jquery-payment', 'prettyPhoto', 'prettyPhoto-init', 'select2', 'selectWoo', 'wc-address-i18n', 'wc-add-payment-method', 'wc-cart', 'wc-cart-fragments', 'wc-checkout', 'wc-country-select', 'wc-credit-card-form', 'wc-add-to-cart', 'wc-add-to-cart-variation', 'wc-geolocation', 'wc-lost-password', 'wc-password-strength-meter', 'photoswipe', 'photoswipe-ui-default', 'wc-add-to-cart-composite');
                    //Authorize.Net Gateway for WooCommerce
                    if (function_exists('wc_authorize_net_cim')) {
                        $arr[] = 'wc-authorize-net-cim';
                        $arr[] = 'wc-authorize-net-apple-pay';
                        $arr[] = 'wc-authorize-net-my-payment-methods';
                        $arr[] = 'sv-wc-payment-gateway-payment-form-v5_8_1';
                        $arr[] = 'sv-wc-payment-gateway-my-payment-methods-v5_8_1';
                        $arr[] = 'sv-wc-jilt-prompt-customers';
                        $arr[] = 'sv-wc-apple-pay-v5_8_1';
                    }
                    if (defined('WOOCOMMERCE_GATEWAY_EWAY_VERSION')) {//plugin eWAY WooCommerce gateway
                        $arr[] = 'eway-credit-card-form';
                    }
                }
                if (defined('WPCF7_PLUGIN')) {
                    $arr[] = 'contact-form-7';
                }
                if (defined('SBI_PLUGIN_DIR')) {//plugin instagram feed
                    $arr[] = 'sb_instagram_scripts';
                }
                if (defined('LP_PLUGIN_FILE')) {//plugin learnpress
                    $arr[] = 'lp-global';
                    $arr[] = 'global';
                    $arr[] = 'learnpress';
                    $arr[] = 'lp-plugins-all';
                    $arr[] = 'learn-press-enroll';
                    $arr[] = 'quiz';
                    $arr[] = 'wp-utils';
                    $arr[] = 'course';
                    $arr[] = 'checkout';
                    $arr[] = 'profile-user';
                    $arr[] = 'become-a-teacher';
                    $arr[] = 'jquery-caret';
                }
            }
        }
        return $arr;
    }

    public static function removeWebp($dir = null) {
        if ($dir === null) {
            $dir = themify_upload_dir('basedir');
            if (!Themify_Filesystem::is_dir($dir) || !Themify_Filesystem::is_readable($dir)) {
                return array('error' => sprintf(__('The directory %s doesn`t exist or not readable', 'themify'), $dir));
            }
        }
        $arr = array('.png', '.jpg', '.jpeg','.gif');
        $files = scandir($dir,SCANDIR_SORT_NONE);
        foreach ($files as $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!Themify_Filesystem::is_dir($path)) {
                if (pathinfo($path, PATHINFO_EXTENSION) === 'webp') {
                    foreach ($arr as $v) {
                        if (Themify_Filesystem::is_file(str_replace('.webp', $v, $path))) {
                            Themify_Filesystem::delete($path,'f');
                            break;
                        }
                    }
                }
            } elseif ($value !== '.' && $value !== '..') {
                self::removeWebp($path);
            }
        }
    }
    /**
     * Load assets required by Themify framework
     *
     * @since 1.1.2
     */
    public static function loadMainScript() {
        //Enqueue main js that will load others needed js
        global $wp_scripts, $wp_version;
        if (!isset($wp_scripts->registered['themify-main-script']) && empty($_GET['legacy-widget-preview'])) { /* disable in Block widget preview */
            wp_enqueue_script('themify-main-script', THEMIFY_URI . '/js/main.js', array('jquery'), THEMIFY_VERSION, true);
            $args = array(
                'breakpoints' => themify_get_breakpoints(),
                'wp' => $wp_version,
                'ajax_url' => admin_url('admin-ajax.php'),
                'map_key' => wp_strip_all_tags( themify_builder_get('setting-google_map_key', 'builder_settings_google_map_key') ?: '' ),
                'bing_map_key' =>wp_strip_all_tags( themify_builder_get('setting-bing_map_key', 'builder_settings_bing_map_key') ?: '' ),
                'menu_tooltips' => [],
                'plugin_url'=>rtrim(plugins_url(),'/'),
                'content_url'=>content_url(),
                'includes_url' => trailingslashit( includes_url() )
            );

            if (!themify_is_lazyloading()) {
                $args['lz'] = 1;
            }
            if (self::$themeVersion !== null) {
                $themeSrc = THEME_URI . '/js/themify-script.js';
                $args['theme_v'] = self::$themeVersion;
                if (!is_admin()) {
                    self::addPrefetchJs($themeSrc, self::$themeVersion);
                }
            }
            if(is_admin()){
                $args['is_admin'] = 1;
            }
            else{
                $args['emailSub']=__('Check this out!', 'themify');
                $args['nop']=__('Check this out!', 'themify');
                $args['lightbox']=themify_lightbox_vars_init();
                if (is_user_logged_in()) {
                    $args['pg_opt_updt'] = __('Update', 'themify');
                    if (current_user_can('edit_posts')) {
                        $args['lgi'] = __('Your uploaded image is too large (%w x %hpx). Please resize it below 1600px and re-upload it.', 'themify');
                    }
                    $post_type = get_post_type_object(get_post_type());
                    $t = $post_type ? $post_type->labels->singular_name : __('Page', 'themify');
                    $args['pg_opt_t'] = sprintf('%s %s', $t, __('Options', 'themify'));
                }

                if (!empty($wp_scripts->registered['wp-embed'])) {
                    $wp_scripts->done[] = 'wp-embed';
                }
                if (self::$themeVersion !== null) {
                    global $wp_styles, $wp_filter;
                    if (isset($wp_filter['wp_head'], $wp_filter['wp_head']->callbacks[7], $wp_filter['wp_head']->callbacks[7]['print_emoji_detection_script'])) {
                        add_filter('wp_resource_hints', array(__CLASS__, 'remove_emoji_prefetch'), 100, 2);
                        if (themify_check('setting-emoji', true)) {
                            $src = apply_filters('script_loader_src', includes_url('js/wp-emoji-release.min.js'), 'concatemoji');
                            if (!empty($src)) {
                                ob_start();
                                print_emoji_detection_script();
                                self::$localiztion['wp_emoji'] = trim(str_replace(array('<script type="text/javascript">', '<script>', '</script>'), array('', ''), ob_get_clean()));
                            }
                        }
                        else {
                            remove_action('wp_print_styles', 'print_emoji_styles');
                        }
                        remove_action('wp_head', 'print_emoji_detection_script', 7);
                        remove_filter('embed_head', 'print_emoji_detection_script');
                    }

                    self::$localiztion['menu_point'] = self::$mobileMenuActive;
                    if (is_singular() && comments_open() && get_option('thread_comments') == 1) {
                        self::$localiztion['commentUrl'] = home_url($wp_scripts->registered['comment-reply']->src);
                        $wp_scripts->done[] = 'comment-reply';
                    }
                    $wp_scripts->done[] = 'wp-playlist';
                    if (apply_filters('wp_video_shortcode_library', 'mediaelement') === 'tf_lazy') {
                        if (!empty($wp_scripts->registered['mediaelement-core'])) {
                            $wp_scripts->done[] = 'mediaelement-core';
                            $wp_scripts->done[] = 'mediaelement-migrate';
                            $wp_scripts->done[] = 'wp-mediaelement';
                        }
                        if (!empty($wp_styles->registered['wp-mediaelement'])) {
                            $wp_styles->done[] = 'wp-mediaelement';
                            $wp_styles->done[] = 'mediaelement';
                        }
                    }
                }
            }
            self::$localiztion += $args;
            unset($args);
            if (!self::$localiztion['bing_map_key']) {
                unset(self::$localiztion['bing_map_key']);
            }
            if (!self::$localiztion['map_key']) {
                unset(self::$localiztion['map_key']);
            }
        }
    }

    public static function remove_emoji_prefetch(array $urls, string $relation_type):array {
        if ($relation_type === 'dns-prefetch') {
            remove_filter('wp_resource_hints', array(__CLASS__, 'remove_emoji_prefetch'), 100);
            foreach ($urls as $k => $v) {
                if (strpos('core/emoji/', $v) !== false) {
                    unset($urls[$k]);
                    break;
                }
            }
        }
        return $urls;
    }

    public static function addLocalization($key, $val, $type = false, $object_val = true) {
        if (self::$localiztion !== null) {
            if (!isset(self::$localiztion[$key])) {
                if ($type === false) {
                    self::$localiztion[$key] = $val;
                } else {
                    self::$localiztion[$key] = array();
                    if ($type === 'arr') {
                        self::$localiztion[$key][] = $val;
                    } else {
                        self::$localiztion[$key][$val] = $object_val;
                    }
                }
            } else {
                if ($type === false) {
                    self::$localiztion[$key] = $val;
                } elseif ($type === 'arr') {
                    self::$localiztion[$key][] = $val;
                } else {
                    self::$localiztion[$key][$val] = $object_val;
                }
            }
        }
    }

    public static function getLocalization():array {
        return self::$localiztion;
    }

    public static function loadGalleryCss() {
        self::add_css('tf_wp_gallery', self::THEMIFY_CSS_MODULES_URI . 'gallery.min.css', null, THEMIFY_VERSION);
    }

    public static function preFetchMasonry() {
        self::addPrefetchJs(THEMIFY_URI . '/js/modules/isotop.js');
    }

    public static function loadFluidMasonryCss($in_footer = false) {
        if (!isset(self::$css['tf_fluid_masonry'])) {
            self::preFetchMasonry();
            self::add_css('tf_fluid_masonry', self::THEMIFY_CSS_MODULES_URI . 'fluid-masonry.css', null, THEMIFY_VERSION, null, $in_footer);
            self::addLocalization('done', 'tf_fluid_masonry', true);
        }
    }

    public static function loadAutoTilesCss() {
        self::loadGridCss('auto_tiles');
    }

    public static function loadinfiniteCss() {
        if (!isset(self::$css['tf_infinite'])) {
            self::add_css('tf_infinite', self::THEMIFY_CSS_MODULES_URI . 'infinite.css', null, THEMIFY_VERSION, null, true);
            self::addLocalization('done', 'tf_infinite', true);
        }
    }

    public static function loadThemeStyleModule(string $file, $media = '',bool $in_footer = false) {
        self::add_css('tf_theme_' . str_replace('/', '_', $file), self::$THEME_CSS_MODULES_URI . $file . '.css', null, self::$themeVersion, $media, $in_footer);
    }

    public static function loadThemeWCStyleModule(string $file, $media = '',bool $in_footer = false) {
        self::add_css('tf_theme_wc_' . str_replace('/', '_', $file), self::$THEME_WC_CSS_MODULES_URI . $file . '.css', null, self::$themeVersion, $media, $in_footer);
    }

    public static function loadGridCss(string $grid, bool $in_footer = false) {
        if (!isset(self::$css['tf_grid_' . $grid]) && in_array($grid, array('list-post', 'grid2-thumb', 'grid2', 'grid3', 'grid4', 'grid5', 'grid6', 'list-large-image', 'list-thumb-image', 'auto_tiles'), true)) {
            if ($grid === 'auto_tiles') {
                self::addPrefetchJs(THEMIFY_URI . '/js/modules/auto-tiles.js');
            }
            self::add_css('tf_grid_' . $grid, THEMIFY_URI . '/css/grids/' . $grid . '.css', null, THEMIFY_VERSION, null, $in_footer);
            if (isset(self::$theme_css_support[$grid])  && self::$THEME_CSS_MODULES_DIR !== null) {
                self::add_css('tf_grid_theme_' . $grid, self::$THEME_CSS_MODULES_URI . 'grids/' . $grid . '.css', null, self::$themeVersion, null, $in_footer);
                self::addLocalization('done', 'tf_grid_theme_' . $grid, true);
            }
            self::addLocalization('done', 'tf_grid_' . $grid, true);
        }
    }

    public static function loadGoogleFonts() {
        if (!defined('THEMIFY_GOOGLE_FONTS') || THEMIFY_GOOGLE_FONTS == true) {
            $fonts = apply_filters('themify_google_fonts', self::$googleFonts);
            $res = array();
            foreach ($fonts as $font) {
                if (!empty($font) && preg_match('/^\w/', $font)) {
                    /* fix the delimiter with multiple weight variants, it should use `,` and not `:`
                      reset the delimiter between font name and first variant */
                    $font = preg_replace('/,/', ':', str_replace(':', ',', $font), 1);
                    $key = explode(':', $font)[0];
                    if (!isset($res[$key])) {
                        $res[$key] = array();
                    }
                    if (strpos($font, ',') !== false || strpos($font, ':') !== false) {
                        $font = str_replace(array($key . ':', $key), array('', ''), explode(',', $font));
                        foreach ($font as $f) {
                            $res[$key][] = $f;
                            /* when loading either italic or non-italic variant, make sure other variant is loaded too */
                            $res[$key][] = strpos( $f, 'i' ) !== false ? (int) $f : $f . 'i';
                        }
                    } else {
                        $res[$key][] = '400';
                        $res[$key][] = '400i';
                    }
                }
            }
            if (!empty($res)) {
                $fonts = array();
                foreach ($res as $k => $v) {
                    $fonts[] = $k . ':' . implode(',', array_keys( array_flip($v)));
                }
                $fonts = implode('%7C', $fonts);
                $path = '://fonts.googleapis.com/css?family=' . $fonts . '&display=swap';
                $_key=Themify_Storage::getHash($fonts);
                $css =Themify_Storage::get($_key,'tf_fg_css_');
                $isNew=false;
                unset($fonts,$res);
                if(!$css){
                    $resp = wp_remote_get('https' . $path, array(
                        'sslverify' => false,
                        'httpversion' => '2',
                        'timeout'=>10,
                        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36'
                    ));
                    $css = wp_remote_retrieve_body($resp);
                    /* validate response's content type */
                    $content_type = wp_remote_retrieve_header( $resp, 'content-type' );
                    if ( is_wp_error( $css ) || strpos( $content_type, 'text/css' ) === false ) {
                        $css='';
                    }
                    $isNew=true;
                    unset($resp);
                }
                if ($css && strpos($css,'fonts.gstatic')!==false) {
                    $donwload=themify_builder_check('setting-gf','setting-gf');
                    if($donwload){
                        $gfFonts=array();
                        $split=explode('@font-face',$css);
                        $css='';
                        $maximum=10;
                        $i=0;
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/media.php';
                        add_filter('wp_handle_sideload_overrides',array(__CLASS__,'sideload_overrides_google_fonts'),9999,1);
                        foreach($split as $v){
                            $v=trim($v);
                            if($v!=='' && $v[0]==='{'){
                                $v=explode(';', str_replace(array('{','}'),'',$v));
                                $arr=array();
                                foreach($v as $styles){
                                    $styles=trim($styles);
                                    if($styles!==''){
                                        $props=explode(':',str_replace('https:','',$styles));
                                        if(isset($props[0],$props[1])){
                                            $prop=trim($props[0]);
                                            $value=trim($props[1]);
                                            if($prop==='src'){
                                                $value=explode(' ',$value)[0];
                                                $value=trim(str_replace(array('url',')','('),'',trim($value)));
                                            }
                                            elseif($prop==='unicode-range'){
                                                $value=str_replace(' ', '', $value);
                                            }
                                            $arr[$prop]=$value;
                                        }
                                        elseif(isset($props[0])){
                                            $arr['subset']=trim($props[0]);
                                        }
                                    }
                                }
                                if(!empty($arr['src'])){
                                    if(strpos($arr['src'],'fonts.gstatic')!==false){
                                        $fkey=$arr['font-family'];
                                        if(isset($arr['font-style'])){
                                            $fkey.=$arr['font-style'];
                                        }
                                        if(isset($arr['font-weight'])){
                                            $fkey.=$arr['font-weight'];
                                        }
                                        if(isset($arr['unicode-range'])){
                                            $fkey.=$arr['unicode-range'];
                                        }
                                        $googleFontsUrl='https:'.$arr['src'];
                                        $slug=Themify_Storage::getHash($fkey);
                                        global $wpdb;
                                        $query = "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_parent=0 AND %s LIMIT 1";
                                        $attachment=$wpdb->get_row(sprintf($query,'post_name="'.esc_sql($slug).'"'));
                                        if(empty($attachment)){
                                            $filename=basename($googleFontsUrl);
                                            $attachment=$wpdb->get_row( sprintf($query, 'guid LIKE "%'.esc_sql($filename).'"'));
                                        }
                                        $url=!empty($attachment)?wp_get_attachment_url($attachment->ID):false;
                                        if($url!==false && !is_file(get_attached_file($attachment->ID))){
                                            $url=false;
                                            wp_delete_attachment($attachment->ID,true);
                                        }
                                        unset($attachment);
                                        if($url===false && $i<$maximum && !Themify_Storage::get($slug)){
                                            Themify_Storage::set($slug,1,MINUTE_IN_SECONDS*2);//set a value to block others threads
                                            $tmp = download_url($googleFontsUrl,10,false );

                                            if(!is_wp_error( $tmp )){
                                                $file = array(
                                                    'size'     => filesize($tmp),
                                                    'name'=> $filename,
                                                    'error'=>0,
                                                    'tmp_name' => $tmp
                                                );
                                                $desc=array(trim(str_replace(array('"',"'"),'',$arr['font-family'])));
                                                if(!empty($arr['subset'])){
                                                    $desc[]=trim(str_replace(array('/','*'),'',$arr['subset']));
                                                }
                                                $desc[]=$arr['font-style'];
                                                $desc[]=$arr['font-weight'];
                                                $attach_id=media_handle_sideload( $file, 0,$filename,array(
                                                    'post_mime_type'=>'font/woff2',
                                                    'post_content'=>'Google Font: '.implode('-',$desc),
                                                    'post_name'=>$slug
                                                ) );
                                                if(!is_wp_error($attach_id)){
                                                    $url=wp_get_attachment_url($attach_id);
                                                    $gfFonts[$googleFontsUrl]=$url;
                                                }
                                                unset($desc,$file,$attach_id);
                                            }
                                            Themify_Storage::delete($slug);
                                            Themify_Filesystem::delete($tmp,'f');
                                        }
                                        elseif($url!==false){
                                            $gfFonts[$googleFontsUrl]=$url;
                                        }
                                        $arr['src']=$url===false?$googleFontsUrl:$url;
                                    }
                                    $css.='@font-face{';
                                    foreach($arr as $prop=>$vv){
                                        if($prop!=='subset'){
                                            if($prop==='src'){
                                                $vv='url('.$vv.') format("woff2")';
                                            }
                                            $css.=$prop.':'.$vv.';';
                                        }
                                    }
                                    $css.='}';
                                }
                            }
                        }
                        unset($split);
                        remove_filter('wp_handle_sideload_overrides',array(__CLASS__,'sideload_overrides_google_fonts'),9999);
                    }
                    if(($isNew===true && !$donwload) || !empty($gfFonts)){
                        if(!empty($gfFonts)){
                            $tmp =Themify_Storage::get($_key,'tf_fg_css_');//maybe another thread has changed it
                            if($tmp){
                                $css=strtr($tmp,$gfFonts);
                            }
                            unset($gfFonts,$tmp);
                        }
                        if($css!==''){
                            if($isNew===true){
                                $css = str_replace(array("\r\n","\n","\r","\t"),'',$css);
                                $css = preg_replace('!\s+!',' ',$css);
                                $css = str_replace(array(' {',' }','{ ','; ',': ',', '),array('{','}','{',';',':',','),$css);
                                $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
                            }
                            $css=str_replace(array('font-style:normal;','font-weight:400;'),'',$css);
                            Themify_Storage::set($_key, $css, MONTH_IN_SECONDS * 6,'tf_fg_css_');
                        }
                        else{
                            Themify_Storage::delete($_key,'tf_fg_css_');
                        }
                    }
                }
                if(!$css){
                    $path = ( is_ssl() ? 'https' : 'http' ) . $path;
                    $css='<link rel="preload" fetchpriority="high" as="style" href="' . $path . '"><link fetchpriority="high" id="themify-google-fonts-css" rel="stylesheet" href="' . $path . '">';
                }
                else{
                    $css='<style id="tf_gf_fonts_style">'.$css.'</style>';
                }
                return $css;
            }
        }
        return null;
    }

    public static function sideload_overrides_google_fonts(array $overrides):array{
        $overrides['test_size']=$overrides['test_type']=false;
        return $overrides;
    }

    public static function addGoogleFont(array $fonts) {
        foreach ($fonts as $v) {
            if(!in_array($v,self::$googleFonts,true)){
                self::$googleFonts[] = $v;
            }
        }
    }

    public static function addMobileMenuCss(string $handler, string $src) {
        if (!isset(self::$css['mobile_concate'][$handler]) && !isset(self::$done[$src])) {
            self::$done[$src] = true;
            self::$css['mobile_concate'][$handler] = $src;
        }
    }

    public static function preLoadAnimtion(bool $only_css = false) {
        static $is = false;
        if ($is === false) {
            self::addPreLoadCss(self::THEMIFY_CSS_MODULES_URI . 'animate.min.css');
            if ($only_css === false) {
                $is = true;
                self::addPreLoadJs(THEMIFY_URI . '/js/modules/animate.js');
            }
        }
    }

    public static function preLoadSwiperJs(string $type = 'prefetch',string $only = 'all') {
        $url = THEMIFY_URI . '/js/modules/themify-carousel.js';
        $sw_url = THEMIFY_URI . '/js/modules/swiper/swiper.min.js';
        $sw_css = self::THEMIFY_CSS_MODULES_URI . 'swiper/swiper.css';
        if ($type === 'prefetch') {
            if ($only === 'all' || $only === 'css') {
                self::addPrefetchCss($sw_css);
            }
            if ($only === 'all' || $only === 'js') {
                self::addPrefetchJs($url);
                self::addPrefetchJs($sw_url);
            }
        } else {
            if ($only === 'all' || $only === 'css') {
                self::addPreLoadCss($sw_css);
            }
            if ($only === 'all' || $only === 'js') {
                self::addPreLoadJs($url);
                self::addPreLoadJs($sw_url);
            }
        }
    }

    public static function preFetchFixedHeaderJs(string $type = 'prefetch') {
        $url = THEMIFY_URI . '/js/modules/fixedheader.js';
        if ($type === 'prefetch') {
            self::addPrefetchJs($url);
        } else {
            self::addPreLoadJs($url);
        }
    }

    public static function preFetchSideMenuJs(string $type = 'prefetch') {
        $url = THEMIFY_URI . '/js/modules/themify-sidemenu.js';
        if ($type === 'prefetch') {
            self::addPrefetchJs($url);
        } else {
            self::addPreLoadJs($url);
        }
    }

    public static function preFetchAnimtion(bool $only_css = false) {
        static $is = false;
        if ($is === false) {
            self::addPrefetchCss(self::THEMIFY_CSS_MODULES_URI . 'animate.min.css');
            if ($only_css === false) {
                $is = true;
                self::addPrefetchJs(THEMIFY_URI . '/js/modules/animate.js');
            }
        }
    }

    public static function clearConcateCss($blog_id = false) {
        $cache_type = $blog_id === 'all' && is_multisite()? 'all' : 'blog';
        $dir = self::getCurrentVersionFolder(($cache_type === 'all' ? false : $blog_id));
        clearstatcache();
        if ($cache_type === 'all') {
            $concate_dir = '/themify-concate/';
            $dir=dirname(str_replace($concate_dir, '', $dir));
            if(strpos($dir,'/blogs.dir/')!==false){
                $dir=dirname($dir);
                $concate_dir='/files'.$concate_dir;
            }
            $dir = rtrim($dir, '/') . '/';
            if (Themify_Filesystem::is_dir($dir) && ($handle = opendir($dir))) {
                set_time_limit(0);
                while (false !== ($f = readdir($handle))) {
                    if ($f !== '.' && $f !== '..') {
                        $f = $dir . $f . $concate_dir;
                        if (Themify_Filesystem::is_dir($f)) {
                            self::markAsDeleteCssFile($f);
                        }
                    }
                }
                closedir($handle);
            }
        } else {
            self::markAsDeleteCssFile($dir);
        }
        TFCache::remove_cache($cache_type, false, $blog_id);
        TFCache::clear_3rd_plugins_cache();
    }

    private static function markAsDeleteCssFile(string $dir) {
        if (is_dir($dir) && ($handle = opendir($dir))) {
            $dir = rtrim($dir, '/') . '/';
            while (false !== ($f = readdir($handle))) {
                if ($f !== '.' && $f !== '..') {
                    if (is_dir($dir . $f)) {
                        self::markAsDeleteCssFile($dir . $f);
                    } else {
                        $ext = pathinfo($f, PATHINFO_EXTENSION);
                        $f = $dir . $f . 'del';
                        if ($ext !== 'cssdel' && strpos($ext, 'cssdel') === false && !Themify_Filesystem::is_file($f)) {
                            $fd = fopen($f, 'w');
                            fclose($fd);
                        }
                    }
                }
            }
            closedir($handle);
        }
    }

    public static function wp_media_playlist(string $html,array $attr, $instance):string {
        if (!isset($attr['type']) || $attr['type'] === 'audio') {
            return self::audio_playlist($attr);
        }
        if($attr['type']==='video' && themify_is_ajax()){
            remove_filter('post_playlist', array(__CLASS__, 'wp_media_playlist'), 100);
            $html= themify_make_lazy(wp_playlist_shortcode($attr));
            add_filter('post_playlist', array(__CLASS__, 'wp_media_playlist'), 100,3);
        }
        return $html;
    }

    public static function audio_playlist(array $attr):string {

        $post = get_post();
        $atts = shortcode_atts(
            array(
                'order' => 'ASC',
                'orderby' => 'menu_order ID',
                'id' => $post ? $post->ID : 0,
                'include' => '',
                'exclude' => '',
                'tracklist' => true,
                'tracknumbers' => true,
                'images' => true,
                'artists' => true
            ),
            $attr,
            'playlist'
        );
        $atts['type'] = 'audio';
        unset($post);
        $showImages = !empty($atts['images']);
        if (empty($attr['tracks'])) {
            $id = (int) $atts['id'];
            $args = array(
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => $atts['type'],
                'order' => $atts['order'],
                'orderby' => $atts['orderby'],
                'no_found_rows' => true
            );

            if (!empty($atts['include'])) {
                $args['include'] = $atts['include'];
                $_attachments = get_posts($args);
                $attachments = array();
                foreach ($_attachments as $val) {
                    $attachments[$val->ID] = $val;
                }
                unset($_attachments);
            } else {
                $args['post_parent'] = $id;
                if (!empty($atts['exclude'])) {
                    $args['exclude'] = $atts['exclude'];
                }
                $attachments = get_children($args);
            }
            unset($args);
            if (empty($attachments)) {
                return '<div></div>';
            }

            if (is_feed()) {
                $output = "\n";
                foreach ($attachments as $att_id => $attachment) {
                    $output .= wp_get_attachment_link($att_id) . "\n";
                }
                return $output;
            }

            $tracks = array();
            $mime_types = wp_get_mime_types();
            $metaArr = array('artist', 'album', 'length_formatted');
            foreach ($attachments as $attachment) {
                $url = wp_get_attachment_url($attachment->ID);
                $ftype = wp_check_filetype($url, $mime_types);
                $track = array(
                    'src' => trim($url),
                    'type' => $ftype['type'],
                    'title' => $attachment->post_title,
                    'caption' => $attachment->post_excerpt
                );
                $meta = wp_get_attachment_metadata($attachment->ID);
                if (!empty($meta)) {
                    $track['meta'] = array();
                    foreach ($metaArr as $m) {
                        if (!empty($meta[$m])) {
                            $track['meta'][$m] = $meta[$m];
                        }
                    }
                }
                if ($showImages === true) {
                    $thumb_id = get_post_thumbnail_id($attachment->ID);
                    if (!empty($thumb_id)) {
                        list( $src, $width, $height ) = wp_get_attachment_image_src($thumb_id, 'thumbnail');
                    } else {
                        $src = wp_mime_type_icon($attachment->ID);
                        $width = 48;
                        $height = 64;
                    }
                    $track['thumb'] = array('src' => $src, 'width' => $width, 'height' => $height);
                }

                $tracks[] = $track;
            }
            $mime_types = $metaArr = $attachments = null;
        } else {
            $tracks = $attr['tracks'];
        }
        $data = array(
            'type' => 'audio',
            'tracklist' => $atts['tracklist'] ? 1 : 0,
            'tracknumbers' => $atts['tracknumbers'] ? 1 : 0,
            'images' => $showImages === true ? 1 : 0,
            'artists' => $atts['artists'] ? 1 : 0,
            'tracks' => $tracks
        );
        $autoplay = !empty($attr['autoplay']) ? ' data-autoplay="1"' : '';
        $loop = !empty($attr['loop']) ? ' data-loop' : '';
        $loop .= !empty($attr['muted']) ? ' muted' : '';
        $tracks = $atts = null;
        $output = '<div class="wp-audio-playlist">' . themify_make_lazy('<audio controls="controls" preload="none"' . $autoplay . $loop . '></audio>');
        $output .= '<script type="application/json" class="tf-playlist-script">' . wp_json_encode($data) . '</script></div>';
        return $output;
    }

    public static function widget_css($instance, $thiz, $args) {
        $id = $thiz->id_base;
        if ($id) {
            if ($id === 'themify-most-commented') {
                $id = 'themify-feature-posts';
            }
            elseif ($id === 'themify-social-links') {
                self::add_css('tf_theme_social_links', self::THEMIFY_CSS_MODULES_URI . 'social-links.css', null, THEMIFY_VERSION);
                self::addLocalization('done', 'tf_theme_social_links', true);
            }
            if(isset(self::$theme_css_support['widget_'.$id])){
                $k = 'tf_theme_widget_' . str_replace('-', '_', $id);
                if (!isset(self::$css[$k])) {
                    self::add_css($k, self::$THEME_CSS_MODULES_URI . 'widgets/' . $id . '.css', null, self::$themeVersion);
                    self::addLocalization('done', $k, true);
                }
            }
        }
        return $instance;
    }

    public static function allow_lazy_protocols(array $protocols):array {
        $protocols[] = 'data';
        return $protocols;
    }

    public static function audio_shortcode(string $html, array $attr, $media, $post_id, $library):string  {
        return $library === 'tf_lazy' ? themify_make_lazy($html) : $html;
    }

    public static function embed(string $cached_html, $url, $attr, $post_id):string {
        return str_replace('data-secret','data-lazy data-secret',$cached_html);
    }

    public static function disable_playlist_template($type){
        remove_action( 'wp_playlist_scripts', 'wp_playlist_scripts' );
    }

    public static function video_shortcode(string $html,array $attr, string $content, $instance):string {
        if (apply_filters('wp_video_shortcode_library', 'mediaelement') === 'tf_lazy') {
            $html_atts = array(
                'preload' => 'none',
                'playsinline'=>1,
                'webkit-playsinline'=>1
            );
            if (!empty($attr['src'])) {
                $video_url = parse_url($attr['src']);
                if (isset($video_url['host']) && ($video_url['host'] === 'www.youtube.com' || $video_url['host'] === 'youtube.com' || $video_url['host'] === 'youtu.be' || $video_url['host'] === 'www.vimeo.com' || $video_url['host'] === 'vimeo.com' || $video_url['host'] === 'player.vimeo.com')) {
                    return $html;
                }
                unset($video_url);
                $html_atts['src'] = esc_url($attr['src']);
            }
            if(!empty($attr['disable_lazy'])){
                $html_atts['data-skip'] = 1;
            }
            if (!empty($attr['id'])) {
                $html_atts['id'] = $attr['id'];
            }
            if (!empty($attr['loop'])) {
                $html_atts['loop'] = 1;
            }
            if (!empty($attr['autoplay'])) {
                $html_atts['data-autoplay'] = 1;
            }
            if (!empty($attr['class'])) {
                $cl = trim(str_replace('wp-video-shortcode', '', $attr['class']));
                if ($cl !== '') {
                    $html_atts['class'] = $cl;
                }
            }
            if (!empty($attr['width'])) {
                $html_atts['width'] = $attr['width'];
            }
            if (!empty($attr['height'])) {
                $html_atts['height'] = $attr['height'];
            }
            if (!empty($attr['poster'])) {
                $html_atts['data-poster'] = esc_url($attr['poster']);
            }
            $muted = isset( $attr['muted'] ) ? wp_validate_boolean( $attr['muted'] ) : false;
            if ( $muted ) {
                $html_atts['muted'] = 1;
            }
            $source='';
            $default_types = wp_get_video_extensions();
            $default_types[] = 'mov';
            $mimes=array();
            foreach ( $default_types as $ext ) {
                if (isset($attr[$ext])) {
                    if(empty($mimes)){
                        $mimes = wp_get_mime_types();
                    }
                    if(!isset($html_atts['src']) || !isset($mimes[$ext])){
                        $type=wp_check_filetype($attr[$ext], $mimes);
                    }
                    if (isset($mimes[$ext])) {
                        $m = $mimes[$ext];
                    } 
                    else {
                        $m = isset($type['type'])?$type['type']:null;
                    }
                    if ($m) {
                        $source .= sprintf('<source type="%s" src="%s"/>', $m, esc_url($attr[$ext]));
                    }
                    if (!isset($html_atts['src']) && isset($type['ext']) && strtolower( $type['ext'] ) === $ext ) {
                        $html_atts['src'] = $attr[ $ext ];
                    }
                }
            }
			if (!empty( $html_atts['src'] ) ) {
				unset($mimes,$default_types);
				if(!isset($html_atts['width']) || !isset($html_atts['height'])){
					/* find video by their media type attribute */
					$size=themify_get_video_size($html_atts['src']);
					if($size!==null){
						if(!isset($html_atts['width']) && $size['w']!==''){
							$html_atts['width'] = $size['w'];
						}
						if(!isset($html_atts['height'])&& $size['h']!==''){
							$html_atts['height'] = $size['h'];
						}
					}
				}
				if(isset($html_atts['width'],$html_atts['height']) && $html_atts['width']>0 && $html_atts['height']>0){
					$html_atts['style']='aspect-ratio:'.($html_atts['width']/$html_atts['height']);
				}
				$html = '<video ' . themify_get_element_attributes($html_atts) . '>'.$source. '</video>' . trim($content);
			}
        }
        return $html;
    }

    public static function media_shortcode_library($library):string {
        return 'tf_lazy';
    }

    public static function load_loop_css($class, $post_type, $layout, $type, $moduleArgs = array(), $slug = false) {
        global $themify;
        if (self::$themeVersion !== null) {//only in themify theme
            if (!empty($themify->post_layout_type) && $themify->post_layout_type !== 'default') {
                $class[] = $themify->post_layout_type;
            }
            if ($post_type === 'product' && themify_is_woocommerce_active()) {
                global $woocommerce_loop;
                if ((isset($woocommerce_loop['name']) && ($woocommerce_loop['name'] === 'related' || $woocommerce_loop['name'] === 'up-sells') ) || wc_get_loop_prop('is_shortcode')) {
                    $layout = (int) wc_get_loop_prop('columns');
                    $index = array_search('columns-' . $layout, $class, true);
                    if ($index !== false) {
                        unset($class[$index]);
                    }
                    $index = array_search('masonry', $class, true);
                    if ($index !== false) {
                        unset($class[$index]);
                    }
                    $index = array_search('infinite', $class, true);
                    if ($index !== false) {
                        unset($class[$index]);
                    }
                    $index = array_search('no-gutter', $class, true);
                    if ($index !== false) {
                        unset($class[$index]);
                    }
                    $layout = $layout === 1 ? 'list-post' : 'grid' . $layout;
                }
            }
        }
        self::loadGridCss($layout);
        if (in_array('masonry', $class, true)) {
            if (!in_array($layout, array('slider', 'auto_tiles'), true) || (!empty($themify->post_filter) && $themify->post_filter !== 'no')) {
                $class[] = 'tf_rel';
                if (in_array('tf_fluid', $class, true)) {
                    self::loadFluidMasonryCss();
                } else {
                    self::preFetchMasonry();
                }
            } else {
                $index = array_search('masonry', $class, true);
                if ($index !== false) {
                    unset($class[$index]);
                }
            }
        }
        $class[] = $layout;
        return array_keys( array_flip($class));
    }

    public static function get_css():array {
        return self::$css;
    }

    /**
     * Check if the file belongs to themify(plugin, FW, theme and etc.)
     *
     * return boolean
     */
    public static function is_themify_file(string $file, string $handler):bool {
        return strpos($file, 'maps.google.com') === false && (strpos($handler, 'themify') !== false || strpos($handler, 'builder-') === 0 || strpos($handler, 'tf-') === 0  || strpos($handler, 'tb_builder') === 0|| strpos($handler, 'tbp') === 0 || (defined('THEME_URI') && strpos($file, THEME_URI) !== false) || preg_match('/themify[\.\-][^\/]*\.js/', $file));// match "themify.*.js" or "themify-*.js"
    }

    public static function loadGuttenbergCss($parsed_block, $source_block) {
        remove_filter('render_block_data', array(__CLASS__, 'loadGuttenbergCss'), PHP_INT_MAX);
        if (!empty(self::$guttenbergCss)) {
            global $wp_styles, $wp_version;
            foreach (self::$guttenbergCss as $k => $src) {
                if (isset($wp_styles->registered[$k])) {
                    $ver = $wp_styles->registered[$k]->ver;
                    if (empty($ver)) {
                        $ver = $wp_version;
                    }
                    if (strpos($src, 'http') === false) {
                        $src = get_site_url(null, $src);
                    }
                    self::add_css($k, $src, $wp_styles->registered[$k]->deps, $ver, $wp_styles->registered[$k]->args);
                }
            }
            self::$guttenbergCss = null;
        }
        return $parsed_block;
    }

    public static function getCurrentVersionFolder($blog_id = false):string {
        global $wp_version;
        $object = wp_get_theme();
        $globalKey = THEMIFY_VERSION . $wp_version . $object->get('Name');
        $globalKey .= self::$themeVersion !== null ? self::$themeVersion : $object->get('Version');
        if (themify_is_woocommerce_active()) {
            $globalKey .= WC()->version;
        }
        $globalKey = (string) crc32($globalKey);
        return themify_upload_dir('basedir') . '/themify-concate/' . $globalKey . '/';
    }

    /** Add schedule four_week
     * array $schedules
     *
     * return array
     */
    public static function cron_schedules(array $schedules):array {
        $schedules['four_week'] = array(
            'interval' => WEEK_IN_SECONDS * 4,
            'display' => '4 weeks'
        );
        return $schedules;
    }

    /** Cron job to remove old concate css files and customizer css files
     * return void
     */
    public static function cron() {

        $path = pathinfo(self::getCurrentVersionFolder());
        $dir = $path['dirname'] . '/';
        if(!class_exists('Themify_Filesystem',false)){
            require_once THEMIFY_DIR . '/class-themify-filesystem.php';
        }
        clearstatcache();
        if (Themify_Filesystem::is_dir($dir) && ($handle = opendir($dir))) {
            $found=false;
            $currentFolder = $path['filename'];
            $globalKey = '-' . $currentFolder . '-'; //Need for Backward Compatibility, can be removed 11.05.2021
            while (false !== ($f = readdir($handle))) {
                if ($f !== '.' && $f !== '..' && $currentFolder !== $f && strpos($f, $globalKey, 5) === false) {
                    Themify_Filesystem::delete($dir . $f);
                    $found=true;
                }
            }
            closedir($handle);
            unset($currentFolder, $dir, $globalKey);
            if($found===true){
                TFCache::clear_3rd_plugins_cache();
                TFCache::remove_cache();
            }
        }
        unset($path);
        $upload_dir = themify_upload_dir('basedir').'/';
        $deperecated=$upload_dir.'tf_images_sizes/';
        if(Themify_Filesystem::is_dir($deperecated)){
            Themify_Filesystem::delete($deperecated);
        }
        $deperecated=$upload_dir.'tf_image_ids/';
        if(Themify_Filesystem::is_dir($deperecated)){
            Themify_Filesystem::delete($deperecated);
        }
        if (self::$themeVersion !== null && Themify_Filesystem::is_dir($upload_dir) && ($handle = opendir($upload_dir))) {//remove old customizer css
            $cssFile = THEMIFY_VERSION . '-' . self::$themeVersion;
            while (false !== ($f = readdir($handle))) {
                if ($f !== '.' && $f !== '..' && strpos($f, 'themify-customizer-') === 0 && strpos($f, $cssFile, 10) === false) {
                    Themify_Filesystem::delete($upload_dir . $f,'f');
                }
            }
            closedir($handle);
        }
        Themify_Storage::cleanDb();
    }

    private static function getPreLoad():string {
        $return = '';
        if(!empty(self::$preLoadMedia)){
            foreach (self::$preLoadMedia as $src => $arr) {
                $type=$arr['t'];
                if ($type === 'style' && isset(self::$css[$src])) {
                    continue;
                }
                if (isset($arr['v'])) {
                    $src = $src . '?ver=' . $arr['v'];
                }
                $extra='';
                if($type === 'font'){
                    $extra=' type="font/' . strtok(pathinfo($src, PATHINFO_EXTENSION), '?') . '" crossorigin';
                }
                elseif($type === 'image' && isset($arr['srcset'])){
                    $extra=' imagesrcset="'.$arr['srcset'].'" imagesizes="'.$arr['sizes'].'"';
                }
                elseif($type=== 'json'){
                    $extra=' type="application/json" crossorigin="anonymous"';
                    $type='fetch';
                }
                $return .= sprintf('<link rel="%s" href="%s" as="%s"%s%s>',
                    $arr['r'],
                    $src,
                    $type,
                    $extra,
                    (isset($arr['i']) ? ' fetchpriority="' . $arr['i'] . '"' : '')
                );
            }
        }
        return $return;
    }

    public static function get_version($url, $ver) {
        return $ver;
    }

    public static function load_search_form_css() {
        remove_action('pre_get_search_form', array(__CLASS__, 'load_search_form_css'), 9);
        self::add_css('tf_search_form', self::THEMIFY_CSS_MODULES_URI . 'search-form.css', null, THEMIFY_VERSION);
        self::addLocalization('done', 'tf_search_form', true);
    }

    public static function body_open(){
        echo '<!--tf_svg_holder-->','<script> </script>';
        $ga=themify_get('setting-ga_m_id', '', true);
        if($ga!==''){
            echo '<noscript><iframe data-no-script src="https://www.googletagmanager.com/ns.html?id=GTM-'.$ga.'" height="0" width="0" style="display:none"></iframe></noscript>',
                 '<script async data-no-optimize="1" data-noptimize="1" data-cfasync="false" data-ga="'.$ga.'" src="data:text/javascript;base64,KGE9PntmdW5jdGlvbiBlKCl7YS5kYXRhTGF5ZXIucHVzaChhcmd1bWVudHMpfWEuZGF0YUxheWVyPWEuZGF0YUxheWVyfHxbXSxlKCJqcyIsbmV3IERhdGUpLGUoImNvbmZpZyIsZG9jdW1lbnQuY3VycmVudFNjcmlwdC5kYXRhc2V0LmdhKSxlKCJldmVudCIsInBhZ2VfdmlldyIpfSkod2luZG93KTs="></script>',
                '<script async data-no-optimize="1" data-noptimize="1" data-cfasync="false" src="https://www.googletagmanager.com/gtag/js?id='.$ga.'"></script>';
        }
    }

    public static function add_theme_support_css($css) {
        self::$theme_css_support[$css]=true;
    }

    public static function remove_theme_support_css($css) {
        unset(self::$theme_css_support[$css]);
    }

    public static function has_theme_support_css($css) {
        return isset(self::$theme_css_support[$css]);
    }

    public static function exclude_main_js( $exclude) {
        if(current_filter()==='autoptimize_filter_js_consider_minified'){
            if($exclude===false){
                $exclude='';
            }
            if($exclude!=='' && is_array($exclude)){
                $exclude[]='themify/';
            }
            else{
                $exclude.='themify/';
            }
        }
        elseif(is_array($exclude)){
            $exclude[] = 'themify-main-script';
            $exclude[] = 'themify/';
            $exclude[] = 'tf_vars';
        }
        else{
            $exclude.= ',themify/';
        }
        return $exclude;
    }

    /**
     * Exclude Themify scripts from being concatenated in Page Optimize plugin
     * @link https://wordpress.org/plugins/page-optimize/
     *
     * @return bool
     */
    public static function Automattic_page_optimize_js_exclude(bool $do_concat,string $handle ):bool {
        if (strpos( $handle, 'themify' ) !== false|| strpos( $handle, 'tb_' ) !== false) {
            return false;
        }
        return $do_concat;
    }
}
Themify_Enqueue_Assets::init();
