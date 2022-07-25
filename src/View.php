<?php

namespace WPOpenAPI;

class View {

	private string $filename;

	public function __construct( string $filename ) {
		$this->filename = $filename;
	}

	public function getFilename() {
		return $this->filename;
	}

	public function render( array $vars ) {
		ob_start();
		extract( $vars );
		require WP_OPENAPI_PLUGIN_PATH . '/resources/views/' . $this->getFilename() . '.php';
		$rendered = ob_get_contents();
		ob_end_clean();
		return $rendered;
	}
}
