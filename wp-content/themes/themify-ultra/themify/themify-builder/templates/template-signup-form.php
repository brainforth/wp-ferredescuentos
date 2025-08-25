<?php
/**
 * Template Sign Up
 *
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-signup-form.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$mod_name=$args['mod_name'];
$element_id = $args['module_ID'];
$fields_args = $args['mod_settings']+ array(
    'mod_title' => '',
    'success_action' => 'c',
    'redirect_to' => '',
    'i_name' => '',
    'i_firstname' => '',
    'i_lastname' => '',
    'i_username' => '',
    'i_email' => '',
    'i_password' => '',
    'i_bio' => '',
    'i_desc' => '',
    'i_submit' => '',
    'l_name' =>'',
    'l_firstname' =>'',
    'l_lastname' => '',
    'l_username' =>'',
    'l_email' => '',
    'l_password' => '',
    'l_bio' => '',
    'l_submit' =>'',
    'desc' =>'',
    'optin' => 'no',
    'optin_label' => '',
    'provider' => '',
    'gdpr' => '',
    'gdpr_label' => '',
    'css' => '',
    'welcome' => '',
    'animation_effect' => '',
);
$container_class = apply_filters( 'themify_builder_module_classes', array(
    'module',
    'module-' . $mod_name,
    $element_id,
    $fields_args['css']
), $mod_name, $element_id, $fields_args );

$container_props = apply_filters( 'themify_builder_module_container_props', self::parse_animation_effect($fields_args,array(
    'class' => implode( ' ', $container_class ),
)), $fields_args, $mod_name, $element_id );
if(Themify_Builder::$frontedit_active===false){
    $container_props['data-lazy']=1;
}
self::sticky_element_props($container_props, $fields_args);
$builder_id = $args['builder_id'];
?>
<!-- module signup form -->
<div <?php echo themify_get_element_attributes( $container_props ); ?>>
    <?php
    if ( ! TB_Signup_Form_Module::signup_enabled() ) {
        if ( current_user_can( 'manage_options' ) ) {
            printf( __( 'Signup is currently disabled. You can enable the option in <a href="%s" target="_blank">Settings > General</a>.', 'themify' ), admin_url( 'options-general.php' ) );
        }
    } else { ?>
        <?php
            $container_props = $container_class = $args =null;
            echo Themify_Builder_Component_Module::get_module_title($fields_args);
        ?>
        <?php if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            echo '<div class="tb_signup_welcome">';
            echo str_replace( [
                    '%site_name%',
                    '%site_description%',
                    '%site_url%',
                    '%user_login%',
                    '%user_email%',
                    '%user_firstname%',
                    '%user_lastname%',
                    '%user_display_name%',
                    '%user_id%'
                ], [
                    get_bloginfo('name'),
                    get_bloginfo('description'),
                    home_url(),
                    esc_html( $current_user->user_login ),
                    esc_html( $current_user->user_email ),
                    esc_html( $current_user->user_firstname ),
                    esc_html( $current_user->user_lastname ),
                    esc_html( $current_user->display_name ),
                    $current_user->ID
                ],
                $fields_args['welcome']
            );
            echo '</div>';
        } else { ?>
            <form class="tb_signup_form tf_rel"
                method="POST"
                <?php if ( 'c' === $fields_args['success_action'] ) : ?>data-redirect="<?php echo esc_url( $fields_args['redirect_to'] ); ?>"<?php endif; ?>
                data-generic-error="<?php esc_attr_e( 'There was an error connecting to server, please try again.', 'themify' ) ?>"
            >
                <div class="tb_signup_messages tb_signup_errors"></div>
                <?php if ( 'm' === $fields_args['success_action'] && !empty( $fields_args['msg_success'] ) ): ?>
                    <div class="tf_hide tb_signup_messages tb_signup_success"><?php echo esc_html( $fields_args['msg_success'] ) ?></div>
                <?php endif; ?>
                <div>
                    <label>
                        <span class="tb_signup_label"><?php if ( $fields_args['i_name'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_name'] ); ?></em><?php endif; ?><?php echo esc_html( $fields_args['l_name'] ) ?></span>
                    </label>
                    <div class="tb_sp_name_wrapper">
                        <div>
                            <label>
                                <input type="text" name="first_n">
                                <span><?php if ( $fields_args['i_firstname'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_firstname'] ); ?></em><?php endif; ?> <?php echo esc_html( $fields_args['l_firstname'] ) ?></span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="text" name="last_n">
                                <span><?php if ( $fields_args['i_lastname'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_lastname'] ); ?></em><?php endif; ?><?php echo esc_html( $fields_args['l_lastname'] ) ?></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div>
                    <label>
                        <span class="tb_signup_label" data-required="yes"><?php if ( $fields_args['i_username'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_username'] ); ?></em><?php endif; ?> <?php echo esc_html( $fields_args['l_username'] ) ?></span>
                        <input type="text" name="user_login" autocapitalize="none" autocomplete="username" required="required">
                    </label>
                </div>
                <div>
                    <label>
                        <span class="tb_signup_label" data-required="yes"><?php if ( $fields_args['i_email'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_email'] ); ?></em><?php endif; ?> <?php echo esc_html( $fields_args['l_email'] ) ?></span>
                        <input type="email" name="user_email" autocomplete="email" required="required">
                    </label>
                </div>
                <div>
                    <label>
                        <span class="tb_signup_label" data-required="yes"><?php if ( $fields_args['i_password'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_password'] ); ?></em><?php endif; ?> <?php echo esc_html( $fields_args['l_password'] ) ?></span>
                        <input type="password" name="pwd" autocomplete="current-password">
                    </label>
                </div>
                <div>
                    <label>
                        <span class="tb_signup_label"><?php if ( $fields_args['i_bio'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_bio'] ); ?></em><?php endif; ?> <?php echo esc_html( $fields_args['l_bio'] ) ?></span>
                        <textarea name="bio"></textarea>
                    </label>
                    <?php if ( $fields_args['desc'] !== '' ): ?>
                        <p><?php if ( $fields_args['i_desc'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_desc'] ); ?></em><?php endif; ?> <?php echo esc_html( $fields_args['desc'] ) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ( $fields_args['optin'] === 'yes' ) : ?>
                    <?php
                    if ( ! class_exists( 'Builder_Optin_Service',false ) ){
                        include_once( THEMIFY_BUILDER_INCLUDES_DIR. '/optin-services/base.php' );
                    }
                    $optin_instance = Builder_Optin_Service::get_providers( $fields_args['provider'],true );
                    if ( $optin_instance ) : ?>
                        <div>
                            <label>
                                <input type="hidden" name="optin-provider" value="<?php esc_attr_e( $fields_args['provider'] ); ?>">
                                <?php
                                foreach ( $optin_instance::get_settings() as $provider_field ) :
                                    if ( isset( $provider_field['id'] ) && isset( $fields_args[ $provider_field['id'] ] ) ) : ?>
                                        <input type="hidden" name="optin-<?php echo $provider_field['id']; ?>" value="<?php esc_attr_e( $fields_args[ $provider_field['id'] ] ); ?>">
                                    <?php endif;
                                endforeach;                     
                                unset($optin_instance);
                                ?>
                                <input type="checkbox" name="optin" value="1"> 
                                <span class="tb_signup_optin"><?php esc_html_e( $fields_args['optin_label'] ) ?></span>
                            </label>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ( $fields_args['gdpr'] === 'on' ) : ?>
                    <div>
                        <label>
                            <input type="checkbox" name="gdpr" required="required"> 
                            <span class="tb_signup_gdpr"><?php esc_html_e( $fields_args['gdpr_label'] ); ?></span>
                        </label>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $fields_args['captcha'] ) ) {
                    echo Themify_Builder_Model::get_captcha_field( $fields_args['captcha'], '<div>', '</div>' );
                }
                ?>

                <button name="tb_submit"><span class="tf_loader"></span> <?php if ( $fields_args['i_submit'] !== '' ) : ?><em><?php echo themify_get_icon( $fields_args['i_submit'] ); ?></em><?php endif; ?> <?php echo esc_html( $fields_args['l_submit'] ) ?></button>
                <input type="hidden" name="tb_post_id" value="<?php echo esc_attr( $builder_id ); ?>">
                <input type="hidden" name="tb_element_id" value="<?php echo esc_attr( str_replace( 'tb_', '', $element_id ) ); ?>">
            </form>
        <?php } // login check ?>
    <?php } // register check ?>
</div><!-- /module signup form -->