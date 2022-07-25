<?php

namespace WPOpenAPI;

use WP_REST_Server;
use WPOpenAPI\Spec\Contact;
use WPOpenAPI\Spec\Info;
use WPOpenAPI\Spec\Path;
use WPOpenAPI\Spec\Server;

class SchemaGenerator {
	private Filters $hooks;
	private WP_REST_Server $restServer;
	private array $siteInfo;

	/**
	 * @param Filters        $hooks
	 * @param array          $siteInfo Array containing admin_email, blogname, blogdescription, siteurl, wp_version options
	 * @param WP_REST_Server $restServer
	 */
	public function __construct( Filters $hooks, array $siteInfo, WP_REST_Server $restServer ) {
		$this->hooks      = $hooks;
		$this->restServer = $restServer;
		$this->siteInfo   = $siteInfo;
	}

	private function generateInfo( $namespaces ): Info {
		$contact = new Contact( $this->siteInfo['admin_email'] );
		$info    = new Info(
			ucfirst( $this->siteInfo['blogname'] ) . ' API',
			$this->siteInfo['blogdescription'],
			$this->siteInfo['wp_version'],
			$contact
		);

		return $this->hooks->applyInfoFilters( $info, array( 'namespaces' => $namespaces ) );
	}

	private function generateServer( $namespaces ): Server {
		$server = new Server( $this->siteInfo['siteurl'] . '/wp-json' );
		return $this->hooks->applyServerFilters( $server, array( 'namespaces' => $namespaces ) );
	}

	public function generate( $requestedNamespace ): array {
		$namespaces = $requestedNamespace === 'all' ? $this->restServer->get_namespaces() : array( $requestedNamespace );

		$base = array(
			'openapi'    => '3.1.0',
			'info'       => $this->generateInfo( $namespaces )->toArray(),
			'servers'    => array(
				$this->generateServer( $namespaces )->toArray(),
			),
			'components' => array(
				'schemas' => array(),
			),
		);

		$paths = array();

		foreach ( $namespaces as $namespace ) {
			foreach ( $this->restServer->get_routes( $namespace ) as $path => $args ) {
				$options     = $this->restServer->get_route_options( $path );
				$schemaTitle = null;
				if ( isset( $options['schema'] ) ) {
					$schema = call_user_func( $options['schema'] );
					if ( isset( $schema['title'] ) ) {
						$schemaTitle                                   = $schema['title'];
						$base['components']['schemas'][ $schemaTitle ] = $schema;
					}
				}
				$path = new Path( $path, $schemaTitle );
				$path->generateOperationsFromRouteArgs( $args );
				$paths[ $path->getPath() ] = $path;
			}
		}

		$base['paths'] = array_map(
			function( $path ) use ( $namespaces ) {
				$path = $this->hooks->applyPathFilter( $path, array( 'namespaces' => $namespaces ) );
				return $path->toArray();
			},
			$paths
		);

		return $base;
	}
}
