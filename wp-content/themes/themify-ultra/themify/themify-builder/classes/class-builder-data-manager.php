<?php
/**
 * Builder Data Manager API
 *
 * ThemifyBuilder_Data_Manager class provide API
 * to get Builder Data, Save Builder Data to Database.
 * 
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Builder Data Manager class.
 *
 * This class provide API to get and update builder data.
 *
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 * @author     Themify
 */
class ThemifyBuilder_Data_Manager {

    /**
     * Builder Meta Key
     * 
     * @access public
     * @const string META_KEY
     */
     
    private const OLD_META_KEY = '_themify_builder_settings';
    
    const META_KEY = '_themify_builder_settings_json';



    /**
     * Constructor
     * 
     * @access public
     */
    public static function init() {
        add_action( 'import_post_meta', array( __CLASS__, 'import_post_meta' ), 10, 3 );
    }


    /**
     * Get Builder Data
     * 
     * @access public
     * @param int $post_id 
     * @return array
     */
    public static function get_data( $post_id,bool $plain_return=false ){
        $data = \get_post_meta( $post_id, self::META_KEY, true );   
        if(!empty($data)){  
            if($plain_return!==true){
                $isArray = \is_array($data);
                try {
                    if($isArray===false){
                        $data =\json_decode( $data, true );
                    }
                    if($isArray===true || (!empty($data) && isset($data[0]) && !isset($data[0]['element_id']))){//is old data?
                        $res=self::update_builder_meta($post_id,$data);
                        $data=\json_decode($res['builder_data'],true);
                    }
                }
                catch (\JsonException $e) {
                    $data=array();
                }
            }
        }
        else{
            $data = \get_post_meta( $post_id, self::OLD_META_KEY, true);
            if(!empty($data)){
                $res=self::update_builder_meta($post_id,stripslashes_deep(maybe_unserialize( $data )));
                $data=$plain_return!==true?\json_decode($res['builder_data'],true):$res['builder_data'];
            } 
        }
        $data = !empty($data)?$data:($plain_return===true?'':array());

        return \apply_filters( 'themify_builder_data', $data, $post_id );
    }
    
    /**
     * Save Builder Data.
     * 
     * @access public
     * @param string|array $builder_data 
     * @param int $post_id 
     * @param string $action 
     * @param string $custom_css 
     */
    public static function save_data($builder_data, $post_id, string $action = 'frontend',$custom_css=null):array {
        /* save the data in json format */
        global $wpdb;
        try{
            $wpdb->query('START TRANSACTION');
            $result=self::update_builder_meta($post_id,$builder_data,$action!=='backend',false);
            unset($builder_data);
            if(!empty($result['mid'])){
                $isRevision=wp_is_post_revision($post_id);
                if($isRevision===false){
                    if ( $action === 'backend' && get_post_type( $post_id ) !== 'tbp_template') {/* disable Static Content for Builder Pro's Templates */
                        $plain_text = self::_get_all_builder_text_content(json_decode($result['builder_data'], true));
                        if (!empty($plain_text)) {
                            $result['static_content'] = self::add_static_content_wrapper($plain_text);
                        }
                        unset($plain_text);
                    }
                    if(class_exists('Themify_Builder_Revisions',false)){
                        Themify_Builder_Revisions::create_revision($post_id,$result['builder_data'],$action);
                    }
                    // Save used GS
                    Themify_Global_Styles::save_used_global_styles($result['builder_data'], $post_id);
                }
                if ( $custom_css!==null ) {
                    if(!empty($custom_css)){
                        update_metadata( 'post', $post_id, 'tbp_custom_css', $custom_css);
                        $result['custom_css'] = $custom_css;
                    }
                    else{
                        delete_metadata( 'post', $post_id, 'tbp_custom_css');
                    }
                    do_action( 'themify_builder_custom_css_updated', $post_id,$action );
                }
                $wpdb->query('COMMIT');
                if($isRevision===false){
                    // update the post modified date time, to indicate the post has been modified
                    self::update_post(array('ID'=>$post_id,'post_modified'=>current_time('mysql'),'post_modified_gmt'=>current_time('mysql', 1)));
                    /**
                     * Fires After Builder Saved.
                     * @param int $post_id
                     */
                    do_action( 'themify_builder_save_data', $post_id );
                }
            }
            else{
                throw new ErrorException(__('Error on buuilder saving','themify'));
            }
        }
        catch(Throwable  $e){   
            $result['mid']=false;
            $wpdb->query('ROLLBACK');
        }
        return $result;
    }
    
    /**
     * Sanitize Builder data before saving
     *
     * @param $kses_filter bool Whether the data should be filtered through wp_kses for sanitization
     */
    private static function json_escape(array &$arr, bool $kses_filter = false ):array{
        foreach($arr as $k=>&$v){
            if(is_string($v)){
                if(trim($v)===''){
                    unset($arr[$k]);
                }
                elseif(isset($v[0]) && ($v[0]==='{' || $v[0]==='[')){//is json?
                    $data=$v;
                    $json=\json_decode($data,true);
                    if(json_last_error()!==JSON_ERROR_NONE){
                        $data=stripslashes_deep($data);
                        $json=\json_decode($data,true);
                        if(json_last_error()!==JSON_ERROR_NONE){
                            $data=stripslashes_deep($data);
                            $json=\json_decode($data,true);
                            if(json_last_error()!==JSON_ERROR_NONE){
                                $data=stripslashes_deep($data);
                                $json=\json_decode($data,true);
                            }
                        }
                    }
                    if($k==='background_image-css'){
                        unset($arr[$k]);
                    }
                    else{
                        $arr[$k]=\is_array($json)?self::json_escape($json, $kses_filter ): ( $kses_filter===true ? \wp_kses_post( $v ) : $v );
                    }
                } 
                elseif ( $kses_filter===true ) {
                    $arr[$k] = \wp_kses_post( $v );
                }
            }
            elseif(is_array($v)){
                $arr[$k]=self::json_escape( $v, $kses_filter );
            }
            elseif($v===null){
                unset($arr[$k]);
            }
        }
        return $arr;
    }
    
    /**
     * Remove unicode sequences back to original character
     */
    public static function json_remove_unicode(array $data ):string {
        return \json_encode( $data, JSON_UNESCAPED_UNICODE );
    }

    /**
     * fix importing Builder contents using WP_Import
     * 
     * @access public
     */
    public static function import_post_meta( $post_id, string $key, $value ) {
        if( $key === self::META_KEY) {
            self::update_builder_meta($post_id, $value);
        }
    }
    
    

    /**
     * Check if content has static content
     * @param string $content 
     */
    public static function has_static_content(string $content ):bool {
        $start=strpos($content,'<!--themify_builder_static-->');
        if($start===false){
            return false;
        }
        $end=strpos($content,'<!--/themify_builder_static-->');
        return ($end!==false && ($start<$end));
    }


    /**
     * Update static content string in the string.
     */
    public static function update_static_content_string( string $replace_string, string $content):string {
        if ( self::has_static_content( $content ) ) {
                        
            $arr = explode('<!--themify_builder_static-->',$content);
            unset($content);
            $html='';
            $max=count($arr);
            foreach($arr as $v){
                if($v!=='' && $max>0 && strpos($v,'<!--/themify_builder_static-->')!==false){
                    --$max;
                    $tmp = explode('<!--/themify_builder_static-->',$v);
                    $html.=$replace_string.$tmp[1];
                    if(isset($tmp[2])){
                        $html.=$tmp[2];
                    }
                    /* make 2nd+ instances of Static Content replaced by '' */
                    $replace_string = '';
                }
                else{
                    $html.=$v;
                }
            }
            unset($arr,$replace_string);
            return self::remove_empty_p($html);
        }
        return $content;
    }


    /**
     * Add static content wrapper
     */
    public static function add_static_content_wrapper( string $string ):string {
        return '<!--themify_builder_static-->' . $string . '<!--/themify_builder_static-->';
    }

    /**
     * Save the builder plain content into post_content
     * 
     * @param int $post_id
     * @param mixed $data 
     */
    private static function save_builder_text_only(int $post_id, $data ):bool {
        if(wp_is_post_revision( $post_id )){
            return false;
        }
        $post = get_post($post_id); 
        if(!empty($post)){
            /* disable Static Content for Builder Pro's Templates */
            if ( $post->post_type === 'tbp_template' ) {
                return true;
            }
            if(!is_array($data)){
                $data=json_decode($data,true);
            }
            $text_only =!empty($data)?self::_get_all_builder_text_content($data ):'';
            $post_content = $post->post_content;
            if ( str_contains( $post_content, '<!-- wp:themify-builder/canvas /-->' ) ) { // using Builder's old Gutenberg tag
                $post_content = str_replace( '<!-- wp:themify-builder/canvas /-->', '<!-- wp:themify-builder/canvas -->' . self::add_static_content_wrapper( $text_only ) . '<!-- /wp:themify-builder/canvas -->', $post_content );
            } elseif ( self::has_static_content( $post_content ) ) {
                $post_content = self::update_static_content_string( self::add_static_content_wrapper( $text_only ), $post_content );
            } else {
                /* add new lines before the static wrapper, in case there are Embeds in the post content */
                $post_content.= "\n\n" . self::add_static_content_wrapper( $text_only );
            }
            return self::update_post(array('ID'=>$post_id,'post_content'=>$post_content));
        }
        return false;
    }
    
    
    private static function removeTags(string $text):string{
        // Remove unnecessary tags.
        $text = preg_replace( '/<\/?div[^>]*\>/i', '', $text );
        $text = preg_replace( '/<\/?span[^>]*\>/i', '', $text );
        $text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
        $text = preg_replace( '/<i [^>]*><\\/i[^>]*>/', '', $text );
        $text = preg_replace( '/ class=".*?"/', '', $text );
        $text = preg_replace( '/<!--(.|\s)*?-->/' , '' , $text );

        // Remove line breaks
        $text = preg_replace( '/(^|[^\n\r])[\r\n](?![\n\r])/', '$1 ', $text );
        return normalize_whitespace( $text );
    }
    /**
     * Get all module output plain content.
     * 
     * @param array $data 
     * @return string
     */
    public static function _get_all_builder_text_content(array $data ):string {
        $data = Themify_Builder::get_builder_modules_list( null, $data );
        $text = array();
        if( is_array( $data ) ) {
            foreach( $data as $module ) {
                if(isset($module['mod_name']) ) {
                    $m=Themify_Builder_Component_Module::load_modules($module['mod_name']);
                    if($m!==''){
						if ( ! isset( $module['mod_settings'] ) || ! is_array( $module['mod_settings'] ) ) {
							$module['mod_settings'] = [];
						}
                        $t = is_string($m) ? $m::get_static_content( $module ) : $m->get_plain_content( $module );
                        if($t!==''){
                            $text[] = self::removeTags($t);
                        }
                    }
                }
            }
        }
        $data=null;
        return implode( "\n", $text );
    }



    /**
     * Remove empty paragraph
     */
    public static function remove_empty_p(string $content ):string {
        return str_replace(array(PHP_EOL .'<!--themify_builder_content-->','<!--/themify_builder_content-->'.PHP_EOL,'<p><!--themify_builder_content-->','<!--/themify_builder_content--></p>'),array('<!--themify_builder_content-->','<!--/themify_builder_content-->','<!--themify_builder_content-->','<!--/themify_builder_content-->'),trim($content));
    }

    /**
     * Save the builder in post meta
     */
    public static function update_builder_meta(int $post_id,$data,bool $static_text=true,bool $transaction=true):array{
        $kses_filter = ! current_user_can( 'unfiltered_html' );
        if(is_array($data)){
            $builder=self::json_escape( $data, $kses_filter );
        }
        elseif(is_string($data)){
            $tmp=array($data);
            $builder=self::json_escape( $tmp, $kses_filter );
            unset($tmp);
            if(!empty($builder)){
                $builder=$builder[0];
            }
        }
        else{
            $builder=array();
        }
        unset($data);

        if(!empty($builder) ){
            $json=json_encode($builder);
            if(strpos($json,'modules')===false && strpos($json,'styling')===false){
                $builder=array();
            }
            $json=null;
            if(isset($builder[0]) && !isset($builder[0]['element_id'])){
                Themify_Builder_Model::generateElementsIds($builder);
                return self::update_builder_meta($post_id,$builder,$static_text,$transaction);
            }
        }

        $isNotEmpty=!empty($builder);
        $builder = apply_filters( 'tb_data_before_save', $builder, $post_id );
        $builder=self::json_remove_unicode($builder);
        $isRevision=wp_is_post_revision( $post_id );
        $mid=false;
        global $wpdb;
        $meta_id = $wpdb->get_row( sprintf("SELECT `meta_id` FROM $wpdb->postmeta WHERE `post_id` = %d AND `meta_key` = '%s' LIMIT 1", $post_id,self::META_KEY ));
        try {
            if($transaction===true){
                $wpdb->query('START TRANSACTION');
            }
            if($isRevision || $static_text===false || self::save_builder_text_only($post_id, $builder)){
                $isUpdate=!empty($meta_id) && !empty($meta_id->meta_id);
                if($isNotEmpty===true){
                    if($isUpdate===true){
                        $meta_id= (int)$meta_id->meta_id;
                        //fires wp hooks
                        do_action( 'update_post_meta', $meta_id, $post_id, self::META_KEY, $builder );
                        do_action( 'update_postmeta', $meta_id, $post_id, self::META_KEY, $builder );
                        $result = $wpdb->update(
                            $wpdb->postmeta,
                            array(
                            'meta_value' =>$builder
                            ),
                            array(
                            'meta_id' => $meta_id
                            ),
                            array('%s'),
                            array('%d')
                        );
                        $mid = $result===false?false:$meta_id;
                    }
                    else{
                        if(!empty($meta_id) && isset($meta_id->meta_id)){//wp bug, if db has gone can be 0
                            $wpdb->query( sprintf( "DELETE FROM $wpdb->postmeta WHERE `post_id` = %d AND `meta_key` = '%s'", $post_id,self::META_KEY ));
                        }
                        //fires wp hooks
                        do_action( 'add_post_meta', $post_id, self::META_KEY, $builder);
                        $result = $wpdb->insert(
                            $wpdb->postmeta,
                            array(
                                'post_id'      => $post_id,
                                'meta_key'   =>  self::META_KEY,
                                'meta_value' =>$builder
                            ),
                            array('%d','%s','%s')
                        );
                        $mid = $result!==false?$wpdb->insert_id:false;
                    }
                }
                else{
                    //Don't use delete_post_meta will remove revision parent builder data
                    $deleted=delete_metadata( 'post', $post_id,self::META_KEY,'',false);//if post meta doesn't exist return false it's a bug,but should return 0 to detect if a query is successes or fail
                    $mid=$deleted===false && $isUpdate===true?false:-1;
                }
                if($mid!==false){
                    if($isRevision===false){
                        //Remove the old data format,Don't use delete_post_meta will remove revision parent builder data
                        delete_metadata( 'post', $post_id,self::OLD_META_KEY,'',false);
                        update_meta_cache('post',$post_id);
                        wp_cache_delete( $post_id, 'posts' );
                        wp_cache_delete( $post_id, 'post_meta' );
                        clean_post_cache( $post_id );
                        TFCache::remove_cache($post_id);
                        themify_clear_menu_cache();
                        TFCache::clear_3rd_plugins_cache($post_id);
                    }
                    if($transaction===true){
                        $wpdb->query('COMMIT');
                    }
                    if($mid!==-1){//fires wp hooks
                        if($isUpdate===true){
                            do_action( 'updated_post_meta', $meta_id, $post_id, self::META_KEY, $builder );
                            do_action( 'update_postmeta', $meta_id, $post_id, self::META_KEY, $builder );
                        }
                        else{
                            do_action( 'added_post_meta', $mid, $post_id, self::META_KEY, $builder );
                        }
                    }
                    if($isRevision===false && $transaction===true){
                        // update the post modified date time, to indicate the post has been modified
                        self::update_post(array('ID'=>$post_id,'post_modified'=>current_time('mysql'),'post_modified_gmt'=>current_time('mysql', 1)));
                    }
                }
                else{
                    throw new ErrorException(__('Error on buuilder saving','themify'));
                }
            }
        }
        catch(Throwable $e){
            $mid=false;
            if($transaction===true){
                $wpdb->query('ROLLBACK');
            }
        }
        return array('mid'=>$mid,'builder_data'=>$builder);
    }
    
    private static function update_post(array $data):bool{
        ob_start();//some plugins output or calls the_content, which break the html
        $res=wp_update_post($data,true);
        ob_clean();
        return !is_wp_error($res);
    }

}
ThemifyBuilder_Data_Manager::init();