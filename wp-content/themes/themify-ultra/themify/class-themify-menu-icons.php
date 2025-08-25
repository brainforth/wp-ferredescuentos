<?php

/**
 * Menu Icons feature
 * 
 * Allows adding custom icons to WordPress menu items.
 * 
 * @package Themify
 * @since 1.6.8
 */
class Themify_Menu_Icons {

    /**
     * Setup menu icon functionality
     *
     * @since 1.6.8
     */
    public static function init() {
        if ( is_admin() ) {
            add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'wp_nav_menu_item_custom_fields' ), 12, 4 );
            add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'wp_update_nav_menu_item' ), 10, 3 );
        } else {
            add_filter( 'nav_menu_item_title', [ __CLASS__, 'nav_menu_item_title' ], 10, 4 );
        }
    }

    public static function nav_menu_item_title( $title, $menu_item, $args, $depth ) {
        if ( $icon = self::get_menu_icon( $menu_item->ID ) ) {
            if ( isset( $args->tf_icon_cb ) ) {
                /* custom callback for menu icons, provided by wp_nav_menu() */
                $callback = $args->tf_icon_cb;
                $title = $callback( $title, $icon, $menu_item, $args, $depth );
            } else {
                $title = '<em> '. themify_get_icon( $icon ) . '</em> ' . $title;
            }
        }

        return $title;
    }

    /**
     * Save the icon meta for a menu item. Also removes the meta entirely if the field is cleared.
     *
     * @since 1.6.8
     */
    static function wp_update_nav_menu_item( $menu_id, $menu_item_db_id, $args ) {
        if ( isset( $_POST['menu-item-icon'][ $menu_item_db_id ] ) ) {
            $meta_key = '_menu_item_icon';
            $meta_value = self::get_menu_icon( $menu_item_db_id );
            $menu_item_icon =  $_POST['menu-item-icon'][ $menu_item_db_id ];
            $new_meta_value = stripcslashes( $menu_item_icon );

            if ( $new_meta_value && '' == $meta_value )
                add_post_meta( $menu_item_db_id, $meta_key, $new_meta_value, true );
            elseif ( $new_meta_value && $new_meta_value != $meta_value )
                update_post_meta( $menu_item_db_id, $meta_key, $new_meta_value );
            elseif ( '' == $new_meta_value && $meta_value )
                delete_post_meta( $menu_item_db_id, $meta_key, $meta_value );
        }
    }

    /**
     * Display the icon picker for menu items in the backend
     *
     * @since 1.6.8
     */
    static function wp_nav_menu_item_custom_fields( $item_id, $item, $depth, $args ) {
        $saved_meta = self::get_menu_icon( $item_id );
        $item_id = esc_attr( $item_id );
    ?> 
        <p class="field-icon description description-thin">
            <label for="edit-menu-item-icon-<?php echo $item_id; ?>">
                <?php _e( 'Icon', 'themify' ) ?><br/>
                <span class="icon-preview font-icon-preview"><i><?php echo themify_get_icon( $saved_meta ); ?></i></span>
                <input type="text" name="menu-item-icon[<?php echo $item_id; ?>]" id="edit-menu-item-icon-<?php echo $item_id?>" size="8" class="edit-menu-item-icon themify_field_icon small-text" value="<?php esc_attr_e( $saved_meta ); ?>">
                <a class="button button-secondary hide-if-no-js themify_fa_toggle" href="#" data-target="#edit-menu-item-icon-<?php echo $item_id ?>"><?php _e( 'Insert Icon', 'themify' ); ?></a>
            </label>
        </p>
    <?php }

    /**
     * Returns the icon name chosen for a given menu item
     *
     * @return string|null
     * @since 1.6.8
     */
    static function get_menu_icon( $item_id ) {
        return get_post_meta( $item_id, '_menu_item_icon', true );
    }
}
Themify_Menu_Icons::init();