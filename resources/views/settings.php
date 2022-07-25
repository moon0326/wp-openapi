<form action='options.php' method='post'>
	<h2>WP OpenAPI Settings</h2>
	<?php
		settings_fields( $groupId );
		do_settings_sections( $pageId );
		submit_button();
	?>
</form>
