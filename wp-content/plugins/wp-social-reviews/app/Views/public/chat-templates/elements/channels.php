<?php
use WPSocialReviews\App\Services\Platforms\Chats\Helper as chatHelper;
use WPSocialReviews\Framework\Support\Arr;

$image_url = chatHelper::getImageUrl($settings);
$prefilled_platforms = chatHelper::getPrefilledPlatform();
$whatsappBgColor = !empty(Arr::get($settings, 'styles.message_background_color')) ? 'background-color:' . Arr::get($settings, 'styles.message_background_color') . ';' : '';
$inputTextColor = !empty(Arr::get($settings, 'styles.message_text_color')) ? 'color:' . Arr::get($settings, 'styles.message_text_color') . ';' : '';
$sendBtnBgColor = !empty(Arr::get($settings, 'styles.send_button_bg_color')) ? 'background-color:' . Arr::get($settings, 'styles.send_button_bg_color') . ';' : '';
$sendBtnIconColor = !empty(Arr::get($settings, 'styles.send_button_icon_color')) ? 'fill:' . Arr::get($settings, 'styles.send_button_icon_color') . ';' : '';
$phoneNumber = Arr::get($settings, 'channels.0.credential');
$prefilledPlaceholderText = Arr::get($settings, 'chat_button.prefilled_placeholder_text', 'Type a message');
?>

<div class="wpsr-fm-chat-btn-wrapper">
    <div class="wpsr-fm-btn-icon">
            <?php if ( $settings['channels'] && sizeof($settings['channels']) === 1){
                $isUrl = chatHelper::isUrl($settings['channels'][0]['credential']);
                $credential = $isUrl ? $settings['channels'][0]['credential'] : $settings['channels'][0]['webUrl'] . $settings['channels'][0]['credential'];
                if(strpos($credential, 'mailto') !== false || strpos($credential, 'tel') !== false){
                    $credential = chatHelper::encodeCredentials($credential);
                }
                $credential = str_replace('=+', '=', $credential);
                $channelName = Arr::get($settings, 'channels.0.name');
                $hasPrefilledMessage = isset($settings['chat_button']['prefilled_message']) && $settings['chat_button']['prefilled_message'] === 'true';
                ?>
                
                <?php if (!(in_array($channelName, $prefilled_platforms)) || !$hasPrefilledMessage) { ?>
                    <a role="button"
                       data-chat-url="<?php echo esc_attr($credential); ?>"
                       data-channel="<?php echo esc_attr($settings['channels'][0]['name']); ?>"
                       style="background-color:<?php echo esc_attr(Arr::get($settings, 'styles.channel_icon_bg_color', '')); ?>"
                       class="wpsr-fm-btn <?php echo esc_attr($settings['channels'][0]['name']); ?>"
                    >
                            <span><?php echo esc_html($settings['chat_button']['button_text']); ?></span>
                            <?php
                            if ($settings['chat_button']['display_icon'] === 'true') {
                                if (strpos($credential, 'fluentform_modal')) {
                                    echo do_shortcode($credential);
                                }
                                if (!strpos($credential, 'fluentform_modal')) {
                                ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($settings['channels'][0]['name']); ?>" width="32" height="32">
                                <?php } ?>
                            <?php } ?>
                    </a>
                <?php } else { ?>
                    <div class="wpsr-prefilled-input-container" data-channel-name="<?php echo esc_attr($channelName); ?>" data-phone-number="<?php echo esc_attr($phoneNumber); ?>" <?php echo ($whatsappBgColor) ? 'style="' . esc_attr($whatsappBgColor) . '"' : ''; ?>>
                        <div class="wpsr-prefilled-input-container-inner">
                            <input type="text" placeholder="<?php echo esc_html($prefilledPlaceholderText); ?>" class="wpsr-prefilled-input" <?php echo ($inputTextColor) ? 'style="' . esc_attr($inputTextColor) . '"' : ''; ?>>
                        </div>
                        <button class="wpsr-prefilled-send-button" <?php echo ($sendBtnBgColor) ? 'style="' . esc_attr($sendBtnBgColor) . '"' : ''; ?>>
                            <svg <?php echo ($sendBtnIconColor) ? 'style="' . esc_attr($sendBtnIconColor) . '"' : ''; ?> viewBox="0 0 24 24" x="0px" y="0px" class="wpsr-prefilled-send-button-icon">
                                <title>send</title>
                                <path d="M1.101,21.757L23.8,12.028L1.101,2.3l0.011,7.912l13.623,1.816L1.112,13.845 L1.101,21.757z"></path>
                            </svg>
                        </button>
                    </div>
                <?php } ?>
            <?php } ?>
            <?php if (sizeof($settings['channels']) > 1){ ?>
            <span class="wpsr-fm-multiple-btn"><?php echo esc_html($settings['chat_button']['button_text']); ?></span>
            <div class="wpsr-channels <?php echo sizeof($settings['channels']) == 1 ? 'wpsr-social-channel' : ''; ?>">
                <?php
                $app->view->render('public.chat-templates.elements.channels-button', array(
                    'templateSettings'   => $templateSettings,
                    'settings'           => $settings
                ));
                ?>
            </div>
            <div class="wpsr-prefilled-input-container" style="display:none" <?php echo ($whatsappBgColor) ? 'style="' . esc_attr($whatsappBgColor) . '"' : ''; ?>>
                <div class="wpsr-prefilled-input-container-inner">
                    <input type="text" placeholder="<?php echo esc_html($prefilledPlaceholderText); ?>" class="wpsr-prefilled-input" <?php echo ($inputTextColor) ? 'style="' . esc_attr($inputTextColor) . '"' : ''; ?>>
                </div>
                <button class="wpsr-prefilled-send-button" <?php echo ($sendBtnBgColor) ? 'style="' . esc_attr($sendBtnBgColor) . '"' : ''; ?>>
                    <svg <?php echo ($sendBtnIconColor) ? 'style="' . esc_attr($sendBtnIconColor) . '"' : ''; ?> viewBox="0 0 24 24" x="0px" y="0px" class="wpsr-prefilled-send-button-icon">
                        <title>send</title>
                        <path d="M1.101,21.757L23.8,12.028L1.101,2.3l0.011,7.912l13.623,1.816L1.112,13.845 L1.101,21.757z"></path>
                    </svg>
                </button>
            </div>
    <?php } ?>
    </div>
</div>