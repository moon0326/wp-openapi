<h2 id="callback">Callback</h2>
<div class='wp-openapi-callback-info-html'>
<?php if ($callbackType === 'function') { ?>
<div class='wp-openapi-callback-info-html-label'>Function</div>
<div class='wp-openapi-callback-info-html-value'><?php echo esc_html($callable[0])?></div>
<?php } else if ($callbackType === 'class') { ?>
<div class='wp-openapi-callback-info-html-label'>Class</div>
<div class='wp-openapi-callback-info-html-value'><?php echo esc_html($callable[0])?></div>
<div class='wp-openapi-callback-info-html-label'>Method</div>
<div class='wp-openapi-callback-info-html-value'><?php echo esc_html($callable[1])?></div>
<?php } ?>

<?php if ($filepath) { ?>
<div class='wp-openapi-callback-info-html-label'>Filepath</div>
<div class='wp-openapi-callback-info-html-value'><?php echo esc_html($filepath)?></div>
<?php } ?>
</div>
