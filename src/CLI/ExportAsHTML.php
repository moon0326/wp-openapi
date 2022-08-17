<?php

namespace WPOpenAPI\CLI;

use WPOpenAPI\Filters;
use WPOpenAPI\SchemaGenerator;
use WPOpenAPI\View;

class ExportAsHTML {

	public function execute( $namespace, $saveTo ) {
		global $wp_version;
		$siteInfo        = array(
			'admin_email'     => get_option( 'admin_email' ),
			'blogname'        => get_option( 'blogname' ),
			'blogdescription' => get_option( 'blogdescription' ),
			'siteurl'         => get_option( 'siteurl' ),
			'wp_version'      => $wp_version,
		);
		$schemaGenerator = new SchemaGenerator( Filters::getInstance(), $siteInfo, rest_get_server() );

		$view = new View( 'export-html' );
		$html = $view->render(
			array(
				'schema' => $schemaGenerator->generate( $namespace ),
				'title'      => $siteInfo['blogname'],
			)
		);

		file_put_contents( $saveTo, $html );

		\WP_CLI::success( "Generated {$saveTo}" );
	}
}
