<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class to create tool buttons like Reset, Import and Export.
 *
 * @since 3.0.5
 */
class Themify_Tools_Control extends WP_Customize_Control {

    /**
     * Type of this control.
     * @access public
     * @var string
     */
    public $type = 'themify_tools';

    /**
     * Render the control's content.
     *
     * @since 3.0.5
     */
    public function render_content() {
        ?>

        <span class="customize-control-title themify-control-title">
            <a href="#" class="tool_wrapper clearall" data-sitename="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" data-tagline="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>">
                <span class="clearall-icon tf_close"></span>
                <?php echo __( 'Clear All', 'themify' ); ?>
            </a>
            <a href="#" class="tool_wrapper customize-import" id="tf_customizer_import"
                data-nonce="<?php echo wp_create_nonce( 'tf_customizer_import' ); ?>"
                data-msg-invalid="<?php esc_attr_e('Missing exported Customizer data.', 'themify') ?>"
                data-msg-success="<?php esc_attr_e('Imported successfully.', 'themify') ?>"
                data-msg-error="<?php esc_attr_e('Trouble connecting to server.', 'themify') ?>"
                data-msg-confirm="<?php esc_attr_e('Import will overwrite all settings and configurations. Press OK to continue, Cancel to stop.', 'themify') ?>"
            >
                <i class="ti-import customize-import-icon"></i>
                <?php _e('Import', 'themify'); ?>
            </a>
            <a class="tool_wrapper" id="tf_customizer_export" href="#" data-nonce="<?php echo wp_create_nonce( 'tf_customizer_export' ) ?>">
                <span class="ti-export customize-export-icon"></span>
                <?php echo __( 'Export', 'themify' ); ?>
            </a>
        </span>

        <input <?php $this->link(); ?> value="" type="hidden" class="<?php echo esc_attr( $this->type ); ?>_control"/>
        <?php
    }
}