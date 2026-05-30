<?php

namespace WPOpenAPI\Filters;

use WPOpenAPI\Filters;
use WPOpenAPI\Spec\Response;
use WPOpenAPI\Spec\ResponseContent;

class FixWPCoreCollectionEndpoints {

	/**
	 * Fallback list of known collection endpoints that don't have a matching single-item endpoint.
	 * These are primarily search/listing endpoints without a corresponding /{id} variant.
	 */
	const WP_CORE_COLLECTION_ENDPOINTS = array(
		'/wp/v2/search',
		'/wp/v2/block-directory/search',
		'/wp/v2/pattern-directory/patterns',
		'/wp/v2/block-patterns/patterns',
		'/wp/v2/block-patterns/categories',
	);


	public function __construct( Filters $hooks ) {
		$hooks->addPathsFilter(function(array $paths) {
			// Build a set of all endpoint paths for collection detection
			$allEndpoints = array();
			foreach ($paths as $path) {
				$allEndpoints[] = $path->getPath();
			}
			$allEndpoints = array_unique($allEndpoints);

			foreach ($paths as $path) {
				foreach ($path->getOperations() as $operation) {
					$endpoint = $operation->getEndpoint();
					$method   = $operation->getMethod();
					if ($method !== 'get') {
						continue;
					}

					// Check if this is a collection endpoint using heuristic or fallback list
					if (!$this->isCollectionEndpoint($endpoint, $allEndpoints, $operation)) {
						continue;
					}

					$response = $operation->getResponse(200);
					if (!$response) {
						continue;
					}

					$newResponse = new Response(
						$response->getCode(),
						$response->getDescription()
					);

					foreach ($response->getContents() as $content) {
						$mediaType = $content->getMediaType();
						$schema    = $content->getSchema();

						$hasValidJsonSchema = (
							$mediaType === 'application/json' &&
							is_array($schema) &&
							isset($schema['$ref'])
						);

						if ($hasValidJsonSchema) {
							$newContent = new ResponseContent(
								'application/json',
								[
									'type'  => 'array',
									'items' => [
										'$ref' => $schema['$ref'],
									],
								]
							);
							$newResponse->addContent($newContent);
						} else {
							$newResponse->addContent($content);
						}
					}

					$operation->addResponse($newResponse);
				}
			}

			return $paths;
		});
	}

	/**
	 * Check if an endpoint is a collection endpoint.
	 *
	 * An endpoint is considered a collection if any of:
	 * 1. The GET operation accepts both `page` and `per_page` query parameters
	 *    (cheap O(1) check, catches listing endpoints like /wc/v3/variations), OR
	 * 2. There exists another endpoint with the same base path plus a path variable
	 *    (e.g., /customers is a collection if /customers/{id} exists), OR
	 * 3. It's in the fallback list of known collection endpoints.
	 */
	private function isCollectionEndpoint(string $endpoint, array $allEndpoints, $operation): bool {
		// Check fallback list first for known collection endpoints without single-item variants
		if (in_array($endpoint, self::WP_CORE_COLLECTION_ENDPOINTS, true)) {
			return true;
		}

		// Pagination heuristic: a GET that accepts both `page` and `per_page` is a collection.
		if ($operation->getParameterByName('page') && $operation->getParameterByName('per_page')) {
			return true;
		}

		// Check if there's a matching single-item endpoint: endpoint + /{variable}
		$pattern = '#^' . preg_quote($endpoint, '#') . '/\{[^}]+\}$#';

		foreach ($allEndpoints as $otherEndpoint) {
			if (preg_match($pattern, $otherEndpoint)) {
				return true;
			}
		}

		return false;
	}
}