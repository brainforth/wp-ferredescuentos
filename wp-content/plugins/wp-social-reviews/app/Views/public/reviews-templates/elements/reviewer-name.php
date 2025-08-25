<?php use WPSocialReviews\App\Services\Helper; ?>
<<?php echo esc_attr($tag); ?> <?php Helper::printInternalString(implode(' ', $attrs)); ?>>
    <span class="wpsr-reviewer-name"><?php echo esc_html($reviewer_name); ?></span>
    <?php if($enable_verified_badge && $enable_verified_badge != 'false'){ ?>
        <span v-if="(enableVerifiedBadge !== 'false' || enableVerifiedBadge === 'true') && (isReviewerName === 'true')" class="wpsr-verified-review" aria-label="Verified Customer">
            <div class="verified-badge-star">
            <div class="checkmark"></div>
            </div>
        </span>
    <?php }?>
</<?php echo esc_attr($tag); ?>>