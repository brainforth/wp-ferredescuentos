<?php

$post_types = apply_filters( 'themify_hooks_visibility_post_types', get_post_types( array( 'public' => true ) ) );
unset( $post_types['page'], $post_types['attachment'] );
$post_types = array_map( 'get_post_type_object', $post_types );

$taxonomies = apply_filters( 'themify_hooks_visibility_taxonomies', get_taxonomies( array( 'public' => true ) ) );
$taxonomies = array_map( 'get_taxonomy', $taxonomies );
?>

<div id="themify_lightbox_visibility" class="themify_lightbox_visibility themify-admin-lightbox tf_clearfix" style="display: none;">
    <h3 class="themify_lightbox_title"><?php _e( 'Condition', 'themify' ) ?></h3>
    <div class="close_lightbox tf_close"></div>
    <div class="lightbox_container">
        <form id="visibility-tabs" class="ui-tabs">
            <ul class="tf_clearfix">
                <li><a href="#visibility-tab-general"><?php _e( 'General', 'themify' ) ?></a></li>
                <li><a href="#visibility-tab-pages" class="themify_hc_load_ajax" data-type="post_type:page"><?php _e( 'Pages', 'themify' ) ?></a></li>
                <li><a href="#visibility-tab-categories-singles" class="themify_hc_load_ajax"><?php _e( 'Has Term', 'themify' ) ?></a></li>
                <li><a href="#visibility-tab-categories" class="themify_hc_load_ajax" data-type="tax:category"><?php _e( 'Categories', 'themify' ) ?></a></li>
                <li><a href="#visibility-tab-post-types" class="themify_hc_load_ajax"><?php _e( 'Post Types', 'themify' ) ?></a></li>
                <li><a href="#visibility-tab-taxonomies" class="themify_hc_load_ajax"><?php _e( 'Taxonomies', 'themify' ) ?></a></li>
                <?php if ( themify_is_woocommerce_active() ) : ?><li><a href="#visibility-tab-wc"><?php _e( 'WooCommerce', 'themify' ) ?></a></li><?php endif; ?>
                <li><a href="#visibility-tab-userroles"><?php _e( 'User Roles', 'themify' ) ?></a></li>
            </ul>

            <div id="visibility-tab-general" class="themify-visibility-options tf_clearfix">
                <label><input type="checkbox" name="general[home]" /><span data-tooltip="<?php echo get_home_url() ?>"><?php _e( 'Home page', 'themify' ) ?></span></label>
                <label><input type="checkbox" name="general[404]" /><?php _e( '404 page', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[page]" /><?php _e( 'Page views', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[single]" /><?php _e( 'Single post views', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[search]" /><?php _e( 'Search pages', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[category]" /><?php _e( 'Category archive', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[tag]" /><?php _e( 'Tag archive', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[author]" /><?php _e( 'Author pages', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[date]" /><?php _e( 'Date archive pages', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[year]" /><?php _e( 'Year based archive', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[month]" /><?php _e( 'Month based archive', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[day]" /><?php _e( 'Day based archive', 'themify' ) ?></label>
                <label><input type="checkbox" name="general[logged]" /><?php _e( 'User logged in', 'themify' ) ?></label>

                <?php
                /* General views for CPT */
                foreach( get_post_types( array( 'public' => true, 'exclude_from_search' => false, '_builtin' => false ) ) as $key => $post_type ) :
                    $post_type = get_post_type_object( $key );
                    ?>
                    <label><input type="checkbox" name="general[<?php echo $key ?>]" /><?php printf( __( 'Single %s View', 'themify' ), $post_type->labels->singular_name ) ?></label>
                    <label><input type="checkbox" name="post_type_archive[<?php echo $key ?>]" /><?php printf( __( '%s Archive View', 'themify' ), $post_type->labels->singular_name ) ?></label>
                <?php endforeach; ?>

                <?php
                /* Custom taxonomies archive view */
                foreach( get_taxonomies( array( 'public' => true, '_builtin' => false ) ) as $key => $tax ) :
                    $tax = get_taxonomy( $key );
                    ?>
                    <label><input type="checkbox" name="general[<?php echo $key ?>]" /><?php printf( __( '%s Archive View', 'themify' ), $tax->label ) ?></label>
                <?php endforeach; ?>

            </div><!-- #visibility-tab-general -->

            <div id="visibility-tab-pages" class="themify-visibility-options themify-visibility-type-options tf_clearfix">
                <div class="themify-visibility-items-inner">
                </div>
            </div><!-- #visibility-tab-pages -->

            <div id="visibility-tab-categories-singles" class="themify-visibility-options tf_clearfix">
                <div id="themify-visibility-category-single-inner-tabs" class="themify-visibility-inner-tabs">
                    <ul class="inline-tabs tf_clearfix">
                        <?php foreach( $taxonomies as $key => $tax ) : ?>
                            <li><a href="#visibility-tab-in_tax-<?php echo $key ?>" data-type="in_tax:<?php echo $key ?>"><?php echo $tax->label ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="themify-visibility-type-options tf_clearfix">
                        <?php foreach( $taxonomies as $key => $tax ) : ?>
                            <div id="visibility-tab-in_tax-<?php echo $key ?>" class="themify-visibility-inner-tab">
                                <div class="themify-visibility-items-inner"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div><!-- #visibility-tab-categories-singles -->

            <div id="visibility-tab-categories" class="themify-visibility-options themify-visibility-type-options tf_clearfix">
                <div class="themify-visibility-items-inner">
                </div>
            </div><!-- #visibility-tab-categories -->

            <div id="visibility-tab-post-types" class="themify-visibility-options tf_clearfix">
                <div id="themify-visibility-post-types-inner-tabs" class="themify-visibility-inner-tabs">
                    <ul class="inline-tabs tf_clearfix">
                        <?php foreach( $post_types as $key => $post_type ) : ?>
                            <li><a href="#visibility-tab-<?php echo $key ?>" data-type="post_type:<?php echo $key ?>"><?php echo $post_type->label ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="themify-visibility-type-options tf_clearfix">
                        <?php foreach( $post_types as $key => $post_type ) : ?>
                            <div id="visibility-tab-<?php echo $key ?>" class="themify-visibility-inner-tab">
                                <div class="themify-visibility-items-inner"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div><!-- #visibility-tab-post-types -->

            <?php
            unset( $taxonomies['category'] );
            ?>
            <div id="visibility-tab-taxonomies" class="themify-visibility-options tf_clearfix">
                <div id="themify-visibility-taxonomies-inner-tabs" class="themify-visibility-inner-tabs">
                    <ul class="inline-tabs tf_clearfix">
                        <?php foreach( $taxonomies as $key => $tax ) : ?>
                            <li><a href="#visibility-tab-<?php echo $key ?>" data-type="tax:<?php echo $key ?>"><?php echo $tax->label ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="themify-visibility-type-options tf_clearfix">
                        <?php foreach( $taxonomies as $key => $tax ) : ?>
                            <div id="visibility-tab-<?php echo $key ?>" class="themify-visibility-inner-tab">
                                <div class="themify-visibility-items-inner"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div><!-- #visibility-tab-taxonomies -->

            <?php if ( themify_is_woocommerce_active() ) : ?>
                <div id="visibility-tab-wc" class="themify-visibility-options tf_clearfix">
                    <label><input type="checkbox" name="wc[orders]" /><?php _e( 'Orders', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[view-order]" /><?php _e( 'View Order', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[downloads]" /><?php _e( 'Downloads', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[edit-account]" /><?php _e( 'Edit Account', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[edit-address]" /><?php _e( 'Edit Address', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[lost-password]" /><?php _e( 'Lost Password', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[order-pay]" /><?php _e( 'Pay', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[order-received]" /><?php _e( 'Order Received', 'themify' ) ?></label>
                    <label><input type="checkbox" name="wc[payment-methods]" /><?php _e( 'Payment Methods', 'themify' ) ?></label>
                </div><!-- #visibility-tab-wc -->
            <?php endif; ?>

            <div id="visibility-tab-userroles" class="themify-visibility-options tf_clearfix">
                <?php foreach( $GLOBALS['wp_roles']->roles as $key => $role ) : ?>
                    <label><input type="checkbox" name="roles[<?php echo $key ?>]" /><?php echo $role['name'] ?></label>
                <?php endforeach; ?>
            </div><!-- #visibility-tab-userroles -->

        </form>
    </div>
    <a href="#" class="uncheck-all"><?php _e( 'Uncheck All', 'themify' ) ?></a>
    <a href="#" class="button button-primary visibility-save alignright"><?php _e( 'Save', 'themify' ) ?></a>
</div>
<div id="themify_lightbox_overlay"></div>