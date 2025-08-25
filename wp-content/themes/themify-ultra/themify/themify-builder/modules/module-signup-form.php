<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Sign Up Form
 * Description: Displays sign up form
 */
class TB_Signup_Form_Module extends Themify_Builder_Component_Module {

    public static function init():void {
        // Sign Up module action for processing sign up form
        add_action('wp_ajax_nopriv_tb_signup_process', array(__CLASS__, 'signup_process'));
    }


    public static function get_module_name():string {
        return __('Sign Up Form', 'themify');
    }

    public static function get_module_icon():string {
        return 'pencil-alt';
    }

    public static function get_js_css():array {
        $_arr = array(
            'css' => 1
        );
        if (!Themify_Builder_Model::is_front_builder_activate()) {
            $_arr['js'] = 1;
        }
        return $_arr;
    }

    /**
     * Render plain content for static content.
     *
     * @param array $module
     *
     * @return string
     */
    public static function get_static_content(array $module):string {
        return sprintf( __( '<a href="%s" target="_blank">Click here to Register</a>', 'themify' ), esc_url( wp_registration_url() ) );
    }

    public static function signup_enabled() {
        return get_option( 'users_can_register' );
    }

    /**
     * Actions to perform when sign up via Sign Up module is sent
     *
     */
    public static function signup_process() {
        if ( empty( $_POST['tb_post_id'] ) || ! self::signup_enabled() ) {
            die(-1);
        }
        $error = false;
        $module = self::get_element_settings( $_POST['tb_post_id'], $_POST['tb_element_id'] );
        if ( empty( $module ) ) {
            $error = __( 'Error retrieving the module.', 'themify' );
        } else if ( $module['captcha'] ) {
            $response =
                $module['captcha'] === 'recaptcha' && ! empty( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response']
                : ( $module['captcha'] === 'hcaptcha' && ! empty( $_POST['h-captcha-response'] ) ? $_POST['h-captcha-response'] : null );
            if ( $response ) {
                $result = Themify_Builder_Model::validate_captcha( $module['captcha'], $response );
                if ( is_wp_error( $result ) ) {
                    $error = $result->get_error_message();
                }
            } else {
                $error = __( 'Captcha response missing.', 'themify' );
            }
        }

        if ( $error === false && self::flood_check() ) {
            $error = __( 'Please wait, and try again.', 'themify' );
        }

        if ( empty( $_POST['user_login'] ) ) {
            $error = __('Please enter a username', 'themify');
        } elseif (!validate_username($_POST['user_login'])) {
            $error = __('Invalid username', 'themify');
        } elseif ( username_exists($_POST['user_login'] ) ) {
            $error = __('Username already taken', 'themify');
        }
        if ( empty( $_POST['user_email'] ) || ! is_email( $_POST['user_email'] ) ) {
            $error = __('Invalid email', 'themify');
        } elseif ( email_exists( $_POST['user_email'] ) ) {
            $error = __('Email already registered', 'themify');
        }
        if ( empty( $_POST['pwd'] ) ) {
            $error = __('Please enter a password', 'themify');
        }

        if ( $error === false ) {
            global $wpdb;
            $wpdb->query( 'START TRANSACTION' );
            $first_name = sanitize_text_field( $_POST['first_n'] );
            $last_name = sanitize_text_field( $_POST['last_n'] );
            $email = sanitize_email( $_POST['user_email'] );
            try {
                $new_user_id = wp_insert_user(array(
                    'user_login' => sanitize_text_field( $_POST['user_login'] ),
                    'user_pass' => sanitize_text_field( $_POST['pwd'] ),
                    'user_email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'description' => isset($_POST['bio']) ? sanitize_textarea_field( $_POST['bio'] ) : '',
                    'user_registered' => date('Y-m-d H:i:s'),
                    )
                );
                if ( is_wp_error( $new_user_id ) ) {
                    throw new Exception( $new_user_id->get_error_message() );
                } else {
                    $wpdb->query( 'COMMIT' );
                }
            }
            catch ( \Throwable $e ) {
                $wpdb->query( 'ROLLBACK' );
                $error = __('Problem creating the new user. Please try again.', 'themify');
            }

            if ( $error === false ) {

                // newsletter subscription
                if (isset($_POST['optin']) && $_POST['optin'] == '1') {
                    if (!class_exists('Builder_Optin_Service',false)) {
                        include_once( THEMIFY_BUILDER_INCLUDES_DIR . '/optin-services/base.php' );
                    }
                    $optin_instance = Builder_Optin_Service::get_providers($_POST['optin-provider'],true);
                    if ($optin_instance) {
                        // collect the data for optin service
                        $data = array(
                            'user_email' => $email,
                            'fname' => $first_name,
                            'lname' => $last_name,
                        );
                        foreach ($_POST as $key => $value) {
                            if (preg_match('/^optin-/', $key)) {
                                $key = preg_replace('/^optin-/', '', $key);
                                $data[ $key ] = sanitize_text_field( trim( $value ) );
                            }
                        }
                        $optin_instance::subscribe($data);
                    }
                    unset($optin_instance);
                }

                $admin_notification = ! isset( $module['e_user'] ) || $module['e_user'] !== false;
                $user_notification = ! isset( $module['e_user'] ) || $module['e_user'] !== false;
                if ( $admin_notification || $user_notification ) {
                    $notify = '';
                    if ( $admin_notification && $user_notification ) {
                        $notify = 'both';
                    } elseif ( $user_notification ) {
                        $notify = 'user';
                    }
                    wp_new_user_notification($new_user_id, null, $notify);
                }

                $_SESSION['tb_signup'] = time();

                wp_send_json_success( [
                    'user_id' => $new_user_id
                ] );
            }
        }

        wp_send_json_error( $error );
    }

    /**
     * Returns true if too many register requests from same IP is detected
     *
     * @return bool
     */
    private static function flood_check():bool {
        if( !session_id() ){
            session_start();
        }
        return ! ( empty( $_SESSION['tb_signup'] ) || ( time() > ( $_SESSION['tb_signup'] + MINUTE_IN_SECONDS ) ) );
    }

    public static function get_styling_image_fields() : array {
        return [
            'b_i' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title', 'optin_label', 'l_name', 'l_firstname', 'l_lastname', 'l_username', 'l_email', 'l_password', 'l_submit' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'msg_success', 'gdpr_label', 'welcome', 'l_bio', 'desc' ];
	}

	public static function get_translatable_link_fields( $module ) : array {
		return [ 'redirect_to' ];
	}
}

TB_Signup_Form_Module::init();