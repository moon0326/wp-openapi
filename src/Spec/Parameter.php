<?php

namespace WPOpenAPI\Spec;

use InvalidArgumentException;
use WPOpenAPI\Util;

class Parameter {

	private $name;
	private $in;
	private $description;
	private $required;
	private $available_in = array( 'query', 'path', 'header', 'cookie' );
	private $type;
	private $jsonSchemaDefinitions = array();
	private $default;

	public function __construct( string $in, string $name, $type, string $description, bool $required ) {
		if ( ! in_array( $in, $this->available_in ) ) {
			throw new InvalidArgumentException(
				"$in is not a valid value. It must be one of " . implode( ',', $this->available_in )
			);
		}
		$this->name        = $name;
		$this->in          = $in;
		$this->description = $description;
		$this->required    = $required;
		$this->type        = $type;
	}

	public function addJsonSchemaDefinition( $key, $value, $location ) {
		$this->jsonSchemaDefinitions[ $key ] = array(
			'value'    => $value,
			'location' => $location,
		);
	}

	public function getName(): string {
		return $this->name;
	}

	public function setDefault( $value ) {
		$this->default = $value;
	}

	public function toArray(): array {
		$data = array(
			'name'        => $this->name,
			'in'          => $this->in,
			'description' => $this->description,
			'required'    => $this->required,
			'schema'      => new \stdClass(),
		);

		if ( $this->default ) {
			$data['schema']->default = $this->default;
		}

		if ( count( $this->jsonSchemaDefinitions ) ) {
			foreach ( $this->jsonSchemaDefinitions as $key => $values ) {
				if ( $values['location'] === 'items' ) {
					if ( ! isset( $data['schema']->items ) ) {
						$data['schema']->items = array();
					}

					$data['schema']->items[ $key ] = $values['value'];
				} else {
					$data['schema']->$key = $values['value'];
				}
			}
			
			// Try to clean up duplicate values added by mistake by plugins.
			// Enum must be unique.
			if ( isset( $data['schema']->enum ) ) {
				$data['schema']->enum = Util::normalizeEnum($data['schema']->enum);
			}

			if ( isset( $data['schema']->items['enum'] ) ) {
				$data['schema']->items['enum'] = Util::normalizeEnum($data['schema']->items['enum']);
			}
		}
		return $data;
	}
}
