<?php

namespace WPOpenAPI\CLI;

use WPOpenAPI;
use WPOpenAPI\Filters;
use WPOpenAPI\Filters\AddCallbackInfoToDescription;
use WPOpenAPI\Filters\FixWPCoreCollectionEndpoints;
use WPOpenAPI\SchemaGenerator;
use WPOpenAPI\SettingsPage;

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

		$restServer = rest_get_server();
		$hooks      = Filters::getInstance();

		if ( SettingsPage::getOption( 'enableCallbackDiscovery' ) === 'on' ) {
			new AddCallbackInfoToDescription( $hooks, new View( 'callback' ), $restServer->get_routes() );
		}

		new FixWPCoreCollectionEndpoints( $hooks );

		$schemaGenerator = new SchemaGenerator( $hooks, $siteInfo, $restServer );
		file_put_contents( $saveTo, json_encode($schemaGenerator->generate( $namespace ), JSON_PRETTY_PRINT) );

		\WP_CLI::success( "Generated {$saveTo}" );
	}
}
