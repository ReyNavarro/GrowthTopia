<?php
function renderBrandMark($size = 'brand-mark')
{
    ?>
<span class="<?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false" shape-rendering="crispEdges">
<path class="brand-lock-shadow" d="M13 18V13a11 11 0 0 1 22 0v5h-5v-5a6 6 0 0 0-12 0v5z"/>
<path class="brand-lock-body" d="M7 17h34v26H7z"/>
<path class="brand-lock-highlight" d="M10 20h28v3H10zm0 20h28v2H10z"/>
<path class="brand-keyhole" d="M24 24a4 4 0 0 0-2 7v7h4v-7a4 4 0 0 0-2-7z"/>
<path class="brand-sprout" d="M23 23v-5h-5v-4h5v-4h4v4h5v4h-5v5zm-1-12c-5-1-7-4-7-7 5 0 8 2 9 6m2 0c1-5 4-7 8-7 0 5-2 8-7 9"/>
</svg></span>
<?php } ?>