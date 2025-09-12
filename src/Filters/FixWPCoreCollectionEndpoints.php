<?php

namespace WPOpenAPI\Filters;

use WPOpenAPI\Filters;
use WPOpenAPI\Spec\Response;
use WPOpenAPI\Spec\ResponseContent;

class FixWPCoreCollectionEndpoints {

	const WP_CORE_COLLECTION_ENDPOINTS = array(
		'/wp/v2/posts',
		'/wp/v2/pages',
		'/wp/v2/media',
		'/wp/v2/menu-items',
		'/wp/v2/blocks',
		'/wp/v2/templates',
		'/wp/v2/template-parts',
		'/wp/v2/navigation',
		'/wp/v2/font-families',
		'/wp/v2/categories',
		'/wp/v2/tags',
		'/wp/v2/menus',
		'/wp/v2/wp_pattern_category',
		'/wp/v2/users',
		'/wp/v2/comments',
		'/wp/v2/search',
		'/wp/v2/block-types',
		'/wp/v2/themes',
		'/wp/v2/plugins',
		'/wp/v2/sidebars',
		'/wp/v2/widget-types',
		'/wp/v2/widgets',
		'/wp/v2/block-directory/search',
		'/wp/v2/pattern-directory/patterns',
		'/wp/v2/block-patterns/patterns',
		'/wp/v2/block-patterns/categories',
		'/wp/v2/font-collections',
		'/wp/v2/posts/{parent}/revisions',
		'/wp/v2/posts/{id}/autosaves',
		'/wp/v2/pages/{parent}/revisions',
		'/wp/v2/pages/{id}/autosaves',
		'/wp/v2/menu-items/{id}/autosaves',
		'/wp/v2/blocks/{parent}/revisions',
		'/wp/v2/blocks/{id}/autosaves',
		'/wp/v2/templates/{parent}/revisions',
		'/wp/v2/templates/{id}/autosaves',
		'/wp/v2/template-parts/{parent}/revisions',
		'/wp/v2/template-parts/{id}/autosaves',
		'/wp/v2/global-styles/{parent}/revisions',
		'/wp/v2/global-styles/themes/{stylesheet}/variations',
		'/wp/v2/navigation/{parent}/revisions',
		'/wp/v2/navigation/{id}/autosaves',
		'/wp/v2/font-families/{font_family_id}/font-faces',
		'/wp/v2/users/{user_id}/application-passwords',
	);


	public function __construct( Filters $hooks ) {
		$hooks->addOperationsFilter(function(array $operations) {
			foreach ($operations as $operation) {
				$endpoint = $operation->getEndpoint();
				$method  = $operation->getMethod();
				if ($method !== 'get') {
					continue;
				}
				if (!in_array($endpoint, self::WP_CORE_COLLECTION_ENDPOINTS, true)) {
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

			return $operations;
		});
	}
}
