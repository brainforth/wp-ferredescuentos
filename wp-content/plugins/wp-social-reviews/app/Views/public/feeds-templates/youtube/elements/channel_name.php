<?php
use WPSocialReviews\Framework\Support\Arr;
$channel_id = Arr::get($header, 'items.0.id', null);
$channel_name = Arr::get($header, 'items.0.snippet.title', '');
?>

<div class="wpsr-yt-header-channel-name">
    <a class="wpsr-yt-header-channel-name-url" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url('https://www.youtube.com/channel/' . $channel_id); ?>"
       title="<?php echo esc_attr($channel_name); ?>"><?php echo esc_html($channel_name); ?>
    </a>
</div>