<?php
if (themify_get('header_wrap') === 'slider') {
    $images = themify_get_gallery_shortcode(themify_get('background_gallery', ''));
    if (!empty($images)) {
	
	Themify_Enqueue_Assets::loadThemeStyleModule('layouts/slider');
	Themify_Enqueue_Assets::loadThemeStyleModule('gallery-controller');

	$slider_args = array(
	    'data-lazy' => 1,
	    'data-effect' => 'fade',
	    'data-auto' => 'yes' === themify_get('background_auto', 'yes') ? themify_get('background_autotimeout', 5) : 0,
	    'data-speed' => themify_get('background_speed', 500),
	    'data-wrapvar' => 'yes' === themify_get('background_wrap', 'yes') ? 1 : 0
	);
	if(themify_check( 'header_hide_controls' )){
	    $slider_attrs['data-pager'] = $slider_attrs['data-slider_nav'] = 0;
	}
	?>
	<div id="gallery-controller" class="tf_w tf_h tf_abs tf_overflow">
		<div <?php echo themify_get_element_attributes($slider_args) ?> class="tf_swiper-container tf_carousel tf_overflow tf_h tf_w tf_clearfix" data-height="auto">
		    <div class="tf_swiper-wrapper tf_lazy tf_h">
				<?php foreach ($images as $i=>$image): ?>
					<?php
					// Get large size for background
					$alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
					if(!$alt){
						$alt=get_post_meta($image->ID, '_wp_attachment_image_title', true);
					}
					?>
					<div class="tf_swiper-slide tf_lazy">
						<?php echo themify_get_image( array( 'w' => '', 'h' => '', 'src' => $image->ID, 'alt'=> $alt,'is_slider'=>$i!==0,'lazy_load'=>$i!==0,'class'=>'tf_w tf_h' ) );?>
					</div>
				<?php endforeach; ?>		
		    </div>
		</div>
	</div>
	<!-- /gallery-controller -->
	<?php
    }
}
