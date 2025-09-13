<?php

namespace WPOpenAPI\CLI;

use WPOpenAPI;
use WPOpenAPI\Filters;
use WPOpenAPI\Filters\AddCallbackInfoToDescription;
use WPOpenAPI\Filters\FixWPCoreCollectionEndpoints;
use WPOpenAPI\SchemaGenerator;
use WPOpenAPI\View;
use WPOpenAPI\SettingsPage;

class ExportAsHTML {

	public function execute( $namespace, $saveTo ) {
		$scriptPath = WPOpenAPI::pluginPath( 'resources/elements/web-components.min.js' );
		$stylePath  = WPOpenAPI::pluginPath( 'resources/elements/styles.min.css' );
		$scripts    = file_get_contents( $scriptPath );
		$styles     = file_get_contents( $stylePath );

		wp_enqueue_script( 'elements-js', $scriptPath );
		wp_enqueue_style( 'elements-style', $stylePath );
		wp_add_inline_script( 'elements-js', $scripts );
		wp_add_inline_style( 'elements-style', $styles );

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

		$view = new View( 'export-html' );
		$html = $view->render(
			array(
				'schema' => $schemaGenerator->generate( $namespace ),
				'title'  => $siteInfo['blogname'],
			)
		);

		file_put_contents( $saveTo, $html );

		\WP_CLI::success( "Generated {$saveTo}" );
	}
}
