<?php

namespace WPOpenAPI\CLI;

use WPOpenAPI;
use WPOpenAPI\Filters;
use WPOpenAPI\SchemaGenerator;

class ExportAsJSON {

	public function execute( $namespace, $saveTo ) {
		global $wp_version;
		$siteInfo        = array(
			'admin_email'     => get_option( 'admin_email' ),
			'blogname'        => get_option( 'blogname' ),
			'blogdescription' => get_option( 'blogdescription' ),
			'home'            => get_option( 'home' ),
			'wp_version'      => $wp_version,
		);
		$schemaGenerator = new SchemaGenerator( Filters::getInstance(), $siteInfo, rest_get_server() );
		file_put_contents( $saveTo, json_encode($schemaGenerator->generate( $namespace ), JSON_PRETTY_PRINT) );

		\WP_CLI::success( "Generated {$saveTo}" );
	}
}
