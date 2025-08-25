<?php

if( ! class_exists( 'Themify_Access_Role',false ) ) :
class Themify_Access_Role {

    public static function init(){
        add_filter( 'themify_theme_config_setup', array( __CLASS__, 'config_setup' ), 14 );
        add_filter( 'admin_init', array( __CLASS__, 'hide_customizer' ), 99 );
        add_filter( 'themify_metabox/fields/themify-meta-boxes', array( __CLASS__, 'hide_custom_panel_and_backend_builder' ), 999 );
    }

    /**
     * Renders the options for role access control
     *
     * @param array $data
     * @return string
     */
    public static function config_view( $data = array() ){
        global $wp_roles;
        $roles = $wp_roles->get_names();
        // Remove the adminitrator and subscriber user role from the array
        unset( $roles['administrator'],$roles['subscriber'] );

        // Get the unique setting name
        $setting = $data['attr']['setting'];

        // Generate prefix with the setting name
        $prefix = 'setting-'.$setting.'-';

        $show_owned_option = in_array( $setting, [ 'tbp', 'frontend', 'backend' ], true );

        ob_start();
        if ( 'custom_panel' === $setting ) :
            ?>
            <div class="themify-info-link"><?php _e( 'Role access allow certain user roles to have access to the tool. Only set disable if you want to disallow the tool to certain user(s), otherwise keep everything as default.', 'themify' ); ?></div>
            <?php
        endif;
        ?>
        <ul>
        <?php foreach( $roles as $role => $slug ) {
                        $prefix_role = esc_attr($prefix.$role);
            // Get value from the database
            $value = themify_builder_get( $prefix_role,$prefix_role);

            // Check if the user has not saved any setting till now, if so, set the 'default' as value
            $value = ( null !== $value ) ? $value : 'default';
                        
            ?>
            <li class="role-access-controller">
                <!-- Set the column title -->
                <div class="role-title">
                    <?php echo $slug; ?>
                </div>

                <!-- Set option to default -->
                <div class="role-option role-default">
                    <input type="radio" id="default-<?php echo $prefix_role; ?>" name="<?php echo $prefix_role; ?>" value="default" <?php echo checked( $value, 'default', false ); ?>/>
                    <label for="default-<?php echo $prefix_role; ?>"><?php _e( 'Default', 'themify' ); ?></label>
                </div>

                <!-- Set option to enable -->
                <div class="role-option role-enable">
                    <input type="radio" id="enable-<?php echo $prefix_role; ?>" name="<?php echo $prefix_role; ?>" value="enable" <?php echo checked( $value, 'enable', false ); ?>/>
                    <label for="enable-<?php echo $prefix_role; ?>"><?php _e( 'Enable', 'themify' ); ?></label>
                </div>

                <?php if ( $show_owned_option ) : ?>
                    <div class="role-option role-enableown">
                        <input type="radio" id="enableown-<?php echo $prefix_role; ?>" name="<?php echo $prefix_role; ?>" value="enableown" <?php echo checked( $value, 'enableown', false ); ?>/>
                        <label for="enableown-<?php echo $prefix_role; ?>"><?php _e( 'Enable For Owned Posts', 'themify' ); ?></label>
                    </div>
                <?php endif; ?>

                <!-- Set option to disable -->
                <div class="role-option role-disable">
                    <input type="radio" id="disable-<?php echo $prefix_role; ?>" name="<?php echo $prefix_role; ?>" value="disable" <?php echo checked( $value, 'disable', false ); ?>/>
                    <label for="disable-<?php echo $prefix_role; ?>"><?php _e( 'Disable', 'themify' ); ?></label>
                </div>
           </li>
        <?php }//end foreach ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    /**
     * Role Access Control
     * @param array $themify_theme_config
     * @return array
     */
    public static function config_setup(array $themify_theme_config ):array {
        // Add role acceess control tab on settings page
        $themify_theme_config['panel']['settings']['tab']['role_access'] = array(
            'title' => __('Role Access', 'themify'),
            'id' => 'role_access',
            'custom-module' => array(
                array(
                    'title' => __('Themify Custom Panel (In Post/Page Edit)', 'themify'),
                    'function' => array( __CLASS__, 'config_view' ),
                    'setting' => 'custom_panel'
                ),
                array(
                    'title' => __('Customizer', 'themify'),
                    'function' => array( __CLASS__, 'config_view' ),
                    'setting' => 'customizer'
                ),
                array(
                    'title' => __('Builder Backend', 'themify'),
                    'function' => array( __CLASS__, 'config_view' ),
                    'setting' => 'backend'
                ),
                array(
                    'title' => __('Builder Frontend', 'themify'),
                    'function' => array( __CLASS__, 'config_view' ),
                    'setting' => 'frontend'
                )
            )
        );

        return $themify_theme_config;
    }

    // Hide Themify Custom Panel and Backend Builder
    public static function hide_custom_panel_and_backend_builder(array $meta ):array {
        if( is_user_logged_in() ){
            $custom_panel = self::check_role_access('custom_panel');
            $backend_builder = self::check_access_backend();
            // Remove Page Builde if disabled from role access control
            if( !$backend_builder || 'disable' === $custom_panel ){
                // Check each meta box for panels
                foreach( $meta as $key => $panel ) {
                    // if page builder id found in meta boxes, unset it
                    // Remove Custom Panel if disabled from role access control
                    if ( (!$backend_builder && 'page-builder' === $panel['id'] ) ||('disable' === $custom_panel &&  'page-builder' !== $panel['id'])) {
                        unset( $meta[ $key ] );
                    }
                }
            }
        }
        return $meta;
    }

    /**
     * Check if user has access to builder's backend editor
     */
    public static function check_access_backend(?int $post_id = null ):bool {
        $has_access = is_user_logged_in() && ((empty($post_id) && current_user_can( 'edit_posts' )) || ($post_id>0 && current_user_can( 'edit_post', $post_id )));
        if ( $has_access === true ) {
            $has_access = 'disable' !== self::check_role_access( 'backend' );

            /* check access to specific $post_id */
            if ( $has_access === true && $post_id > 0 ) {
                $has_access === current_user_can( 'edit_post', $post_id );
                if ( $has_access === true && 'enableown' === self::check_role_access( 'backend' ) && ! self::is_current_user_the_author( $post_id ) ) {
                    $has_access = false;
                }
            }
        }

        return $has_access;
    }
    
    /**
     * Check if user has access to builder's frontend editor
     */
    public static function check_access_frontend(?int $post_id = null):bool {
        $has_access = is_user_logged_in() && ((empty($post_id) && current_user_can( 'edit_posts' )) || ($post_id>0 && current_user_can( 'edit_post', $post_id )));
        if ( $has_access === true ) {
            if ( 'enableown' === self::check_role_access( 'frontend' ) ) {
                $has_access = self::is_current_user_the_author( $post_id );
            } 
            else{
                $has_access =  'disable' !== self::check_role_access( 'frontend' );
            }
        }

        return $has_access;
    }
        
    private static function get_current_role(){
        static $user = null;
        if( $user === null ) {
            $user = wp_get_current_user();
            $roles = ! empty( $user->roles ) && is_array( $user->roles ) ? $user->roles : array();
            // Get first role ( don't use key )
            $user = array_shift( $roles );
        }
        return $user;
    }

    // Hide Themify Builder Customizer
    public static function hide_customizer( $data ) {
        if( is_user_logged_in() ){
            $is_available = current_user_can('customize');
            $value = self::check_role_access('customizer');
            // get the the role object
            $editor = get_role(self::get_current_role());
            if ( 'enable' === $value && !$is_available) {
                // add $cap capability to this role object
                $editor->add_cap('edit_theme_options');
            } elseif( 'disable' === $value &&  $is_available) {
                $editor->remove_cap('edit_theme_options');
            }
        }

        return $data;
    }

    public static function check_role_access(string $key){
        $setting = 'setting-'.$key.'-'.self::get_current_role();
        return themify_builder_get( $setting, $setting,true );
    }

    /**
     * Returns true if current logged-in user is the author of $post
     */
    private static function is_current_user_the_author( $post ):bool {
        $post = get_post( $post );
        return $post && (int) $post->post_author === wp_get_current_user()->ID;
    }
}

Themify_Access_Role::init();
endif;
