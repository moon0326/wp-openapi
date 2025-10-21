<?php

namespace WPOpenAPI\Filters;

use WPOpenAPI\Filters;
use WP_REST_Server;
use WP_REST_Request;

class AddACFSchema {
	private Filters $hooks;
	private WP_REST_Server $restServer;
	private array $schemaCache = array();

	public function __construct( Filters $hooks, WP_REST_Server $restServer ) {
		$this->hooks      = $hooks;
		$this->restServer = $restServer;

		$this->hooks->addComponentsFilter( array( $this, 'augmentComponentsWithACF' ), 10 );
		$this->hooks->addOperationsFilter( array( $this, 'augmentOperationsWithACF' ), 10 );
	}

	/**
	 * Fetch ACF schema for a given route via OPTIONS request
	 *
	 * @param string $route The REST API route
	 * @return array|null The ACF schema if found, null otherwise
	 */
	protected function fetchACFSchema( string $route ): ?array {
		// Check cache first
		if ( isset( $this->schemaCache[ $route ] ) ) {
			return $this->schemaCache[ $route ];
		}

		// Perform OPTIONS request to get schema
		$request  = new WP_REST_Request( 'OPTIONS', $route );
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) || $response->get_status() !== 200 ) {
			$this->schemaCache[ $route ] = null;
			return null;
		}

		$data = $response->get_data();

		// Check if ACF schema exists in the response
		if ( isset( $data['schema']['properties']['acf'] ) ) {
			$acfSchema = $data['schema']['properties']['acf'];
			
			// Normalize the ACF schema to ensure all properties have types
			$acfSchema = $this->normalizeACFSchema( $acfSchema );
			
			$this->schemaCache[ $route ] = $acfSchema;
			return $acfSchema;
		}

		$this->schemaCache[ $route ] = null;
		return null;
	}

	/**
	 * Normalize ACF schema to ensure all properties have valid types
	 * This handles clone fields and other ACF field types that might not include explicit types
	 *
	 * @param array $schema The ACF schema to normalize
	 * @return array The normalized schema
	 */
	protected function normalizeACFSchema( array $schema ): array {
		// Ensure the schema has a type
		if ( ! isset( $schema['type'] ) && isset( $schema['properties'] ) ) {
			$schema['type'] = 'object';
		}

		// Convert exact-match patterns to enums for better TypeScript support
		// This enables type narrowing in openapi-typescript for discriminator fields like acf_fc_layout
		if ( isset( $schema['pattern'] ) && is_string( $schema['pattern'] ) ) {
			// Match patterns like ^value$ (exact string match)
			if ( preg_match( '/^\^(.+)\$$/', $schema['pattern'], $matches ) ) {
				$value = $matches[1];
				// Convert to enum array
				$schema['enum'] = array( $value );
				// Remove the pattern property
				unset( $schema['pattern'] );
			}
		}

		// Recursively normalize properties
		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $key => $property ) {
				if ( is_array( $property ) ) {
					$schema['properties'][ $key ] = $this->normalizeACFSchema( $property );
				}
			}
		}

		// Handle items in arrays
		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$schema['items'] = $this->normalizeACFSchema( $schema['items'] );
		}

		// Handle oneOf, anyOf, allOf
		foreach ( array( 'oneOf', 'anyOf', 'allOf' ) as $combiner ) {
			if ( isset( $schema[ $combiner ] ) && is_array( $schema[ $combiner ] ) ) {
				foreach ( $schema[ $combiner ] as $index => $subSchema ) {
					if ( is_array( $subSchema ) ) {
						$schema[ $combiner ][ $index ] = $this->normalizeACFSchema( $subSchema );
					}
				}
			}
		}

		return $schema;
	}

	/**
	 * Find a representative route for a given schema title
	 *
	 * @param string $schemaTitle The schema title from components
	 * @return string|null A route that uses this schema, or null
	 */
	protected function findRouteForSchema( string $schemaTitle ): ?string {
		$routes = $this->restServer->get_routes();

		foreach ( $routes as $route => $handlers ) {
			$options = $this->restServer->get_route_options( $route );
			
			if ( ! isset( $options['schema'] ) ) {
				continue;
			}

			$schema = call_user_func( $options['schema'] );
			
			if ( isset( $schema['title'] ) ) {
				$normalizedTitle = \WPOpenAPI\Util::normalizeSchemaTitle( $schema['title'] );
				if ( $normalizedTitle === $schemaTitle ) {
					// Return a concrete route without regex patterns if possible
					// For routes with parameters, we need to find a concrete example
					// For now, return the route as-is since we're doing OPTIONS
					return $route;
				}
			}
		}

		return null;
	}

	/**
	 * Augment component schemas with ACF properties
	 *
	 * @param array $components The components array
	 * @param array $args Additional arguments
	 * @return array Modified components
	 */
	public function augmentComponentsWithACF( array $components, array $args = array() ): array {
		if ( ! isset( $components['schemas'] ) || ! is_array( $components['schemas'] ) ) {
			return $components;
		}

		foreach ( $components['schemas'] as $schemaTitle => $schema ) {
			// Find a route that uses this schema
			$route = $this->findRouteForSchema( $schemaTitle );
			
			if ( ! $route ) {
				continue;
			}

			// Fetch ACF schema for this route
			$acfSchema = $this->fetchACFSchema( $route );
			
			if ( ! $acfSchema ) {
				continue;
			}

			// Ensure schema has properties key
			if ( ! isset( $schema['properties'] ) ) {
				$schema['properties'] = array();
			}

			// Add ACF property to the schema
			$schema['properties']['acf'] = $acfSchema;

			// Update the component schema
			$components['schemas'][ $schemaTitle ] = $schema;
		}

		return $components;
	}

	/**
	 * Augment operations with ACF properties in requestBody
	 *
	 * @param array $operations The operations array
	 * @param array $args Additional arguments
	 * @return array Modified operations
	 */
	public function augmentOperationsWithACF( array $operations, array $args = array() ): array {
		foreach ( $operations as $operation ) {
			$method   = $operation->getMethod();
			$endpoint = $operation->getEndpoint();

			// Only add ACF to write operations
			if ( ! in_array( $method, array( 'post', 'put', 'patch' ), true ) ) {
				continue;
			}

			// Fetch ACF schema for this endpoint
			$acfSchema = $this->fetchACFSchema( $endpoint );
			
			if ( ! $acfSchema ) {
				continue;
			}

			// Set ACF schema on the operation
			$operation->setACFSchema( $acfSchema );
		}

		return $operations;
	}
}

