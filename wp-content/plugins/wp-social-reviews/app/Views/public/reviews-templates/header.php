<?php
use WPSocialReviews\Framework\Support\Arr;

$sliderData              = Arr::get($template_meta, 'carousel_settings');
$show_header             = Arr::get($template_meta, 'show_header');
$header_enable           = $show_header && $template_meta['show_header'] === 'true' ? 'wpsr-header-enable' : '';
$masonry                 = $templateType === 'masonry' ? 'wpsr-active-masonry-layout' : '';
$fixed_height            = $show_header && $template_meta['show_header'] === 'true' ? 'wpsr-fixed-height' : '';
$content_length_deactive = isset($template_meta['contentType']) && $template_meta['contentType'] === 'content_in_scrollbar' ? 'wpsr-reviews-content-length-deactive' : '';
$template                = isset($template_meta['template']) ? $template_meta['template'] : '';
$has_equal_height        = Arr::get($template_meta, 'equal_height', 'false');
$equal_height            = $has_equal_height === 'true' && $template_meta['contentType'] === 'excerpt' ? 'wpsr-has-equal-height' : '';
$hasSlider               = $templateType === 'slider' && defined('WPSOCIALREVIEWS_PRO') ? 'wpsr-reviews-slider-wrapper' : '';
$wrapperId               = $templateType === 'slider' && defined('WPSOCIALREVIEWS_PRO') ? 'wpsr-reviews-slider-'.$templateId : 'wpsr-reviews-grid-'.$templateId;
$position                = $templateType === 'notification' ? 'wpsr-'.$template_meta['notification_settings']['notification_position'] : '';
$desktop_column_number   = Arr::get($template_meta, 'responsive_column_number.desktop');
$data_platforms = is_array($platforms) ? implode(',', $platforms) : $platforms;

$ai_enabled = Arr::get($template_meta, 'ai_summary.enabled', false);

$wrapperAttsArray = [
    'id' => esc_attr($wrapperId),
    'class' => 'wpsr-reviews-' . esc_attr($templateId) . '  wpsr-reviews-wrapper wpsr-feed-wrap wpsr_content '.esc_attr($position) .' '.esc_attr($hasSlider) . ' '.esc_attr($equal_height). ' ' . esc_attr($content_length_deactive) .' wpsr-reviews-template-'. esc_attr($template).' wpsr-reviews-layout-'.esc_attr($templateType).' '.esc_attr($header_enable),
    'data-column' => $desktop_column_number,
    'data-platforms' => $data_platforms,
];

if($templateType === 'slider' && defined('WPSOCIALREVIEWS_PRO')) {
    $wrapperAttsArray['data-slider_settings'] = wp_json_encode($sliderData);
}

$wrapperAtts = '';

foreach ($wrapperAttsArray as $key => $value) {
    $wrapperAtts .= esc_attr($key)."='".esc_attr($value)."' ";
}

echo wp_kses('<div ' . $wrapperAtts . '>', array(
    'div' => array(
        'id' => true,
        'class' => true,
        'data-column' => true,
        'data-slider_settings' => true,
        'data-platforms' => true,
    )
));

if($templateType === 'badge'){
    echo '<a class="wpsr-popup-close" href="#">
    <svg viewBox="0 0 16 16" style="fill: rgb(255, 255, 255);">
       <path d="M3.426 2.024l.094.083L8 6.586l4.48-4.479a1 1 0 011.497 1.32l-.083.095L9.414 8l4.48 4.478a1 1 0 01-1.32 1.498l-.094-.083L8 9.413l-4.48 4.48a1 1 0 01-1.497-1.32l.083-.095L6.585 8 2.106 3.522a1 1 0 011.32-1.498z">
       </path>
    </svg>
    </a>';
}

if($templateType === 'notification') {
    echo '<a class="wpsr-popup-collapse" href="#" data-notification_id="'.esc_attr($templateId).'">
      <i class="icon-angle-right"></i>
      </a>';
}

$template_width = Arr::get($template_meta, 'template_width', '');
$template_max_width = $template_width ? 'max-width:' .$template_width. 'px;' : '';

echo '<div class="wpsr-container ' . esc_attr($fixed_height) . '" style="'.esc_attr($template_max_width).'" >';

do_action('wpsocialreviews/render_reviews_template_business_info', $reviews, $business_info, $template_meta, $templateId, $translations);

if($template_meta['enable_schema'] === 'true'){
    $name = Arr::get($template_meta, 'schema_settings.business_name');
    $type = Arr::get($template_meta, 'schema_settings.business_type');
    $image = Arr::get($template_meta, 'schema_settings.business_logo');
    $telephone = Arr::get($template_meta, 'schema_settings.business_telephone');
    $business_average_rating = Arr::get($template_meta, 'schema_settings.business_average_rating');
    $business_total_rating   = Arr::get($template_meta, 'schema_settings.business_total_rating');
    $include_business_address = Arr::get($template_meta, 'schema_settings.include_business_address');
    $average_rating = Arr::get($business_info, 'average_rating', 0);
    $ratingValue = $business_average_rating ?: $average_rating;

    $total_rating = Arr::get($business_info, 'total_rating', 0);
    $ratingCount = $business_total_rating ?: $total_rating;

    $schema_array = [
        '@context' => 'https://schema.org/',
        '@type' => 'AggregateRating',
        'itemReviewed' => [
            '@type' => $type,
            'image' => $image,
            'name' => $name,
            'telephone' => $telephone
        ],
        'ratingValue' => number_format($ratingValue, 1),
        'bestRating' => '5',
        'ratingCount' => $ratingCount
    ];

    if($include_business_address === 'true'){
        $business_street_address = Arr::get($template_meta, 'schema_settings.business_street_address');
        $business_city = Arr::get($template_meta, 'schema_settings.business_address_city');
        $business_state = Arr::get($template_meta, 'schema_settings.business_address_state');
        $business_zip = Arr::get($template_meta, 'schema_settings.business_address_postal_code');
        $business_country = Arr::get($template_meta, 'schema_settings.business_address_country');
        $address = [
            '@type' => 'PostalAddress',
            'streetAddress' => $business_street_address,
            'addressLocality' => $business_city,
            'addressRegion' => $business_state,
            'postalCode' => $business_zip,
            'addressCountry' => $business_country
        ];
        $schema_array['itemReviewed']['address'] = $address;
    }

    $schema_array = apply_filters('wpsocialreviews/reviews_schema_data', $schema_array, [
        'template_meta' => $template_meta,
        'business_info' => $business_info,
        'reviews' => $reviews,
        'template_id' => $templateId
    ]);

    echo '<script type="application/ld+json">';
    echo wp_json_encode($schema_array);
    echo '</script>';
}


$template_height = Arr::get($template_meta, 'template_height', '');
$template_style = $template_height ? 'height:' .$template_height. 'px;' : '';
echo isset($template_meta['show_header']) && $template_meta['show_header'] === 'true' ? '<div class="wpsr-row" style="' . esc_attr($template_style) . '">' : '';

do_action('wpsocialreviews/reviews_template_wrapper_start');

if($templateType === 'slider' && defined('WPSOCIALREVIEWS_PRO')) {
    echo '<div class="wpsr-reviews-slider-wrapper-inner">';
}

$has_header = $show_header && $template_meta['show_header'] === 'true' ? 'wpsr-review-fixed-height-wrap' : 'wpsr-row';
$should_add_top_padding = ($has_header === 'wpsr-review-fixed-height-wrap' && $ai_enabled === 'true') ? 'wpsr-reviews-with-top-padding' : '';
$has_header = ($templateType === 'grid' && $ai_enabled === 'true') ? 'wpsr-ai-summary-enabled' : $has_header;

$ai_enabled_class = '';
if($ai_enabled === 'true' && ($templateType === 'grid' || $templateType === 'notification')){
    $ai_enabled_class = 'wpsr-ai-summary-enabled';
} else {
    $ai_enabled_class = 'wpsr-ai-summary-enabled-'.$templateType;
}



echo ($templateType === 'slider' && defined('WPSOCIALREVIEWS_PRO')) ? '<div class="wpsr-reviews-slider swiper-container '.esc_attr($ai_enabled_class).'" tabindex="0">'
    : '<div class="wpsr-all-reviews wpsr_feeds '. esc_attr($ai_enabled_class) . ' ' . esc_attr($has_header) . ' ' . esc_attr($masonry) . ' ' . esc_attr($should_add_top_padding).'" data-column="' . esc_attr($desktop_column_number) . '">';
if($templateType === 'slider' && defined('WPSOCIALREVIEWS_PRO')) {
    echo '<div class="swiper-wrapper">';
}