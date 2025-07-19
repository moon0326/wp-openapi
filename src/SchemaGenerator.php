<?php

namespace WPOpenAPI;

use WP_REST_Server;
use WPOpenAPI\Spec\Contact;
use WPOpenAPI\Spec\Info;
use WPOpenAPI\Spec\Path;
use WPOpenAPI\Spec\Server;
use WPOpenAPI\Spec\Tag;
use WPOpenAPI\Util;

class SchemaGenerator {
	private Filters $hooks;
	private WP_REST_Server $restServer;
	private array $siteInfo;

	/**
	 * @param Filters        $hooks
	 * @param array          $siteInfo Array containing admin_email, blogname, blogdescription, home, wp_version options
	 * @param WP_REST_Server $restServer
	 */
	public function __construct( Filters $hooks, array $siteInfo, WP_REST_Server $restServer ) {
		$this->hooks      = $hooks;
		$this->restServer = $restServer;
		$this->siteInfo   = $siteInfo;
	}

	private function generateInfo( array $hookArgs ): Info {
		$contact = new Contact(
			$this->siteInfo['blogname'],
			$this->siteInfo['home'],
			$this->siteInfo['admin_email']
		);

		$info = new Info(
			ucfirst( $this->siteInfo['blogname'] ) . ' API',
			$this->siteInfo['wp_version'],
			$this->siteInfo['blogdescription'],
			$contact
		);

		return $this->hooks->applyInfoFilters( $info, $hookArgs );
	}

	public function generate( $requestedNamespace ): array {
		$namespaces = $requestedNamespace === 'all' ? $this->restServer->get_namespaces() : array( $requestedNamespace );

		$hookArgs = array(
			'requestedNamespace' => $requestedNamespace,
		);

		$base = array(
			'openapi'    => '3.1.0',
			'info'       => $this->generateInfo( $hookArgs )->toArray(),
			'servers'    => array(
				new Server( $this->siteInfo['home'] . '/' . rest_get_url_prefix() ),
			),
			'tags'       => array(),
			'components' => array(
				'schemas' => array(),
			),
		);

		$paths = array();

		foreach ( $namespaces as $namespace ) {
			$base['tags'][] = new Tag( $namespace );
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

		$base['servers'] = array_map(
			function( Server $server ) {
				return $server->toArray();
			},
			$this->hooks->applyServersFilters( $base['servers'], $hookArgs )
		);

		$base['paths'] = array_map(
			function( $path ) use ( $hookArgs ) {
				return $path->toArray();
			},
			$this->hooks->applyPathsFilters( $paths, $hookArgs )
		);

		$base['tags'] = array_map(
			function ( Tag $tag ) use ( $hookArgs ) {
				return $tag->toArray();
			},
			$this->hooks->applyTagsFilters( $base['tags'] )
		);

		$base['components'] = $this->hooks->applyComponentsFilters( $base['components'], $hookArgs );

		return $this->fixComponentsSchemas( $base );
	}

	/**
	 * This function tries to fix invalid schemas.
	 * Many schemas from the core are invalid. It's not this plugin's responsibility to fix them, but we can at least try to fix some of them
	 * since this plugin is for WP.
	 */
	protected function fixComponentsSchemas( array $base ): array {
		// Fix components schemas
		// Remove context and readonly from the schema.
		// These are not valid in the OpenAPI schema properties.
		// Also remove required from the properties. Property level required is not valid in the OpenAPI schema.
		foreach ( $base['components']['schemas'] as $key =>$schema ) {
			$keyToRemove = isset($schema['properties']) ? 'properties' : 'items';
			if (isset($schema[$keyToRemove])) {
				$base['components']['schemas'][$key][$keyToRemove] = Util::removeArrayKeysRecursively( $schema[$keyToRemove], array( 'context', 'readonly' ) );
			}

			// Remove 'required' from the properties. Property level required is not valid in the OpenAPI schema.
			Util::modifyArrayValueByKeyRecursive($base['components']['schemas'][$key], 'properties', function($properties) {
				if (is_array($properties) && count($properties) === 0) {
					return new \stdClass();
				}

				foreach ($properties as $key => $property) {
					if (isset($property['required'])) {
						unset($properties[$key]['required']);
					}
				}
				return $properties;
			});

			// Fix invalid enum values.
			Util::modifyArrayValueByKeyRecursive($base['components']['schemas'][$key], 'enum', function($enum) {
				if (Util::is_assoc_array($enum)) {
					return array_values($enum);
				}

				return $enum;
			});
		}

		return $base;
	}
}
