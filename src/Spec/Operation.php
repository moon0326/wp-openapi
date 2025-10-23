<?php

namespace WPOpenAPI\Spec;

use InvalidArgumentException;
use WPOpenAPI\Util;

class Operation {
	const METHODS            = array( 'get', 'post', 'patch', 'delete', 'put' );
	private array $tags      = array();
	private ?string $summary = null;
	private string $method;

	private ?string $description = null;
	private ?string $operationId = null;

	private string $endpoint;

	/**
	 * @var Parameter[]
	 */
	private array $parameters = array();

	/**
	 * @var Response[]
	 */
	private array $responses                   = array();
	private array $requestBodySchemaProperties = array();
	private array $securities                  = array();
	private ?array $acfSchema                  = null;

	private array $jsonSchemaSets = array(
		'oneOf'                => array( 'location' => 'root' ),
		'anyOf'                => array( 'location' => 'items' ),
		'items'                => array( 'location' => 'root' ),
		'format'               => array( 'location' => 'root' ),
		'minLength'            => array( 'location' => 'root' ),
		'maxLength'            => array( 'location' => 'root' ),
		'minimum'              => array( 'location' => 'root' ),
		'maximum'              => array( 'location' => 'root' ),
		'multipleOf'           => array( 'location' => 'root' ),
		'minItems'             => array( 'location' => 'root' ),
		'maxItems'             => array( 'location' => 'root' ),
		'uniqueItems'          => array( 'location' => 'root' ),
		'additionalProperties' => array( 'location' => 'root' ),
		'patternProperties'    => array( 'location' => 'root' ),
		'minProperties'        => array( 'location' => 'root' ),
		'maxProperties'        => array( 'location' => 'root' ),
		'enum'                 => array( 'location' => 'root' ),
        'properties'           => array( 'location' => 'root' ),
	);

	public function __construct( string $method, array $responses, string $endpoint ) {
		$method = strtolower( $method );
		if ( ! in_array( $method, self::METHODS ) ) {
			throw new InvalidArgumentException(
				"
                $method is not allowed. It must be one of " . implode( ',', self::METHODS )
			);
		}
		$this->method    = $method;
		$this->responses = $responses;
		foreach ( $responses as $response ) {
			$this->addResponse( $response );
		}
		$this->endpoint  = $endpoint;
	}

	public function getEndpoint(): string {
		return $this->endpoint;
	}

	public function getMethod(): string {
		return $this->method;
	}

	public function addResponse( Response $response ) {
		$this->responses[$response->getCode()] = $response;
	}

	public function getResponse( $code ) {
	    if ( isset( $this->responses[$code] ) ) {
			return $this->responses[$code];
		}

		return null;
	}

	public function addParameter( Parameter $parameter ) {
		$this->parameters[] = $parameter;
	}

	public function addTag( $tag ): void {
		$this->tags[] = $tag;
	}

	public function setSummary( string $summary ): void {
		$this->summary = $summary;
	}

	public function setDescription( string $description ): void {
		$this->description = $description;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function setOperationId( string $operationId ): void {
		$this->operationId = $operationId;
	}

    public function addSecurity( $name, $values ): void {
        $this->securities[] = (object) array( $name => $values );
    }

	/**
	 * Set ACF schema for this operation's request body
	 *
	 * @param array $acfSchema The ACF schema to add
	 * @return void
	 */
	public function setACFSchema( array $acfSchema ): void {
		$this->acfSchema = $acfSchema;
	}

	public function toArray(): array {
		$data = array(
			'responses' => array(),
		);

		foreach ( $this->responses as $response ) {
			$data['responses'][ $response->getCode() ] = $response->toArray();
		}

		if ( count( $this->tags ) ) {
			$data['tags'] = $this->tags;
		}

		if ( $this->summary ) {
			$data['summary'] = $this->summary;
		}

		if ( $this->description ) {
			$data['description'] = $this->description;
		}

		if ( $this->operationId ) {
			$data['operationId'] = $this->operationId;
		}

		if ( count( $this->parameters ) ) {
			$data['parameters'] = array_map(
				function( Parameter $parameter ) {
					return $parameter->toArray();
				},
				$this->parameters
			);
		}

        if ( $this->securities ) {
            $data['security'] = $this->securities;
        }

		if ( count( $this->requestBodySchemaProperties ) || $this->acfSchema ) {
			$schema = array(
				'type'       => 'object',
				'properties' => $this->requestBodySchemaProperties,
			);

			// Add ACF schema if present
			if ( $this->acfSchema ) {
				$schema['properties']['acf'] = $this->acfSchema;
			}

			$requiredProperties = array();
			foreach ( $this->requestBodySchemaProperties as $name => $property ) {
				if ( isset( $property['required'] ) && $property['required'] === true ) {
					$requiredProperties[] = $name;
				}
			}

			if ( count( $requiredProperties ) ) {
				$schema['required'] = $requiredProperties;
			}

			$schema = Util::removeArrayKeysRecursively( $schema, array( 'context', 'readonly' ) );
			Util::modifyArrayValueByKeyRecursive($schema, 'type', function($type) {
				return Util::normalzieInvalidType($type);
			});

			Util::modifyArrayValueByKeyRecursive($schema, 'properties', function($properties) {
				if (is_array($properties) && count($properties) === 0) {
					return new \stdClass();
				}
				foreach ($properties as $key => $property) {
					if (isset($property['required'])) {
						unset($properties[$key]['required']);
					}
				}

				// Loop through again make sure it's not an empty array.
				foreach ($properties as $key => $property) {
					if (is_array($property) && count($property) === 0) {
						$properties[$key] = new \stdClass();
					}
					foreach ($property as $propKey => $propValue) {
						if ($propKey === 'items' && is_string($propValue)) {
							$properties[$key]['items'] = array('type' => Util::normalzieInvalidType($propValue));
						}
					}
				}


				return $properties;
			});


			$data['requestBody'] = array(
				'content' => array(
					'application/x-www-form-urlencoded' => array(
						'schema' => $schema,
					),
				),
			);
		}

		return $data;
	}

	private function addRequestSchemaProperties( $name, $values ) {
		$data = array();
		$pick = array(
			'$schema',
			'$id',
			'$ref',
			'$comment',
			"title",
			"description",
			"default",
			"examples",
			"definitions",
			"type",
			"enum",
			"const",
			"allOf",
			"anyOf",
			"oneOf",
			"not",
			"if",
			"then",
			"else",
			"properties",
			"required",
			"additionalProperties",
			"patternProperties",
			"dependencies",
			"propertyNames",
			"items",
			"additionalItems",
			"contains",
			"minProperties",
			"maxProperties",
			"minItems",
			"maxItems",
			"uniqueItems",
			"minLength",
			"maxLength",
			"pattern",
			"format",
			"contentMediaType",
			"contentEncoding",
			"multipleOf",
			"maximum",
			"exclusiveMaximum",
			"minimum",
			"exclusiveMinimum",
		);
		foreach ( $pick as $valueName ) {
			if ( isset( $values[ $valueName ] ) ) {
				$data[ $valueName ] = $values[ $valueName ];
			}
		}
		$this->requestBodySchemaProperties[ $name ] = $data;
	}

	public function getParameterByName( $name ): ?Parameter {
		foreach ( $this->parameters as $parameter ) {
			if ( $parameter->getName() === $name ) {
				return $parameter;
			}
		}

		return null;
	}

	public function generateParametersFromRouteArgs( $method, array $args, array $pathVariables ): void {
		// get, method, delete, put
		foreach ( $args as $name => $values ) {
			if (isset($values['context'])) {
				$values = Util::removeArrayKeysRecursively( $values, array( 'context', 'readonly' ) );
			}
			if ( in_array( $name, $pathVariables ) ) {
				$in = 'path';
				// required must be set to true when the in value is 'path'
				$values['required'] = true;
			} elseif ( $method === 'get' ) {
				$in = 'query';
			} else {
				$this->addRequestSchemaProperties( $name, $values );
				continue;
			}

			if ( ! isset( $values['description'] ) ) {
				$values['description'] = '';
			}

			// monkey patch for invalid $values['required'].
			// make sure it is a boolean.
			// We don't have a full control over what other plugins 
			// are passing to us. So we need to be careful.
			if ( ! isset( $values['required'] ) || ! is_bool( $values['required'] ) ) {
				$values['required'] = false;
			}

			if ( ! isset( $values['type'] ) ) {
				$values['type'] = 'string';
			}

			$values['type'] = Util::normalzieInvalidType( $values['type'] );

			$parameter = new Parameter( $in, $name, $values['type'], $values['description'], $values['required'] );
			if ( isset( $values['default'] ) ) {
				$parameter->setDefault( $values['default'] );
			}
			
			$supportedJsonSchemaSets = array_keys( $this->jsonSchemaSets );
			foreach ( $values as $key => $value ) {
				if ( in_array( $key, $supportedJsonSchemaSets ) ) {
					$parameter->addJsonSchemaDefinition( $key, $value, $this->jsonSchemaSets[ $key ]['location'] );
				}
			}     

			$this->parameters[] = $parameter;
		}

		// WP doesn't require path variables defined as args. It's optional.
		// Fill them in in case they are missing.
		foreach ( $pathVariables as $pathVariable ) {
			if ( ! $this->getParameterByName( $pathVariable ) ) {
				$this->parameters[] = new Parameter( 'path', $pathVariable, 'string', '', true );
			}
		}
	}
}
