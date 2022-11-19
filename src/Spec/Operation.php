<?php

namespace WPOpenAPI\Spec;

use InvalidArgumentException;

class Operation {
	const METHODS            = array( 'get', 'post', 'patch', 'delete', 'put' );
	private array $tags      = array();
	private ?string $summary = null;
	private string $method;

	private ?string $description = null;
	private ?string $operationId = null;

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
	);

	public function __construct( string $method, array $responses ) {
		$method = strtolower( $method );
		if ( ! in_array( $method, self::METHODS ) ) {
			throw new InvalidArgumentException(
				"
                $method is not allowed. It must be one of " . implode( ',', self::METHODS )
			);
		}
		$this->method    = $method;
		$this->responses = $responses;
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

	public function addSecurity( $security ) {
		$this->securities[] = $security;
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
			$data['security'] = array();
			foreach ( $this->securities as $security ) {
				$data['security'][] = array( $security => array() );
			}
		}

		if ( count( $this->requestBodySchemaProperties ) ) {
			$schema = array(
				'type'       => 'object',
				'properties' => $this->requestBodySchemaProperties,
			);

			$requiredProperties = array();
			foreach ( $this->requestBodySchemaProperties as $name => $property ) {
				if ( isset( $property['required'] ) && $property['required'] === true ) {
					$requiredProperties[] = $name;
				}
			}

			if ( count( $requiredProperties ) ) {
				$schema['required'] = $requiredProperties;
			}

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
		$pick = array( 'type', 'required', 'description' );
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

	public function generateParametersFromRouteArgs( $method, array $args, array $pathVariables, $endpoint ): void {
		// get, method, delete, put
		foreach ( $args as $name => $values ) {
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

			if ( ! isset( $values['required'] ) ) {
				$values['required'] = false;
			}

			if ( ! isset( $values['type'] ) ) {
				$values['type'] = 'string';
			}

			$parameter               = new Parameter( $in, $name, $values['type'], $values['description'], $values['required'] );
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
