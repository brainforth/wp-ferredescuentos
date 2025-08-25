<?php
global $post;
if (!is_object($post))
    return;
?>
<div class="tb_fixed_scroll" id="tb_fixed_bottom_scroll"></div>
<div class="tb_loader tf_loader tf_abs_c tf_box tf_hide"></div>
<style id="tf_lazy_common">
    img{max-width:100%;height:auto}.tf_fa{display:inline-block;width:1em;height:1em;stroke-width:0;stroke:currentColor;overflow:visible;fill:currentColor;pointer-events:none;vertical-align:middle}#tf_svg symbol{overflow:visible}.tf_lazy{position:relative;visibility:visible;display:block;opacity:.3}
</style>