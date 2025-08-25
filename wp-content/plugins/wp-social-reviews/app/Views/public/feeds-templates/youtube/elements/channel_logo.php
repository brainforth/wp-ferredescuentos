<?php
use WPSocialReviews\Framework\Support\Arr;
$channel_avatar = Arr::get($header, 'avatar', '');
$channel_id = Arr::get($header, 'items.0.id', null);
$channel_name = Arr::get($header, 'items.0.snippet.title', '');
?>
<div class="wpsr-yt-header-logo">
    <a class="wpsr-yt-header-logo-url" target="_blank"
       rel="noopener noreferrer"
       href="<?php echo esc_url('https://www.youtube.com/channel/' . $channel_id); ?>">
        <img class="wpsr-yt-header-img-render" src="<?php echo esc_url($channel_avatar); ?>"
             :alt="<?php echo esc_attr($channel_name); ?>">
    </a>
</div>