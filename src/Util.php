<?php

namespace WPOpenAPI;

class Util {
	public static function removeArrayKeysRecursively( array $array, array $keysToRemove ): array {
		foreach ($array as $key => &$value) {
			if (in_array($key, $keysToRemove, true)) {
				unset($array[$key]);
			} elseif (is_array($value)) {
				$value = self::removeArrayKeysRecursively($value, $keysToRemove);
			}
		}

		return $array;
	}

	public static function is_assoc_array(array $array): bool {
		return array_keys($array) !== range(0, count($array) - 1);
	}

	public static function modifyArrayValueByKeyRecursive(array &$array, $key, callable $callback): void {
		foreach ($array as $k => &$v) {
			if ($k === $key) {
				$v = $callback($v);
			}

			if (is_array($v)) {
				self::modifyArrayValueByKeyRecursive($v, $key, $callback);
			}
		}
	}

	/**
	 * Recursively normalizes a schema by fixing invalid types.
	 * Converts types like 'date-time' to proper 'type: string, format: date-time'.
	 *
	 * Accepts either:
	 * - A string (type value) - returns normalized schema array
	 * - An array (full schema) - returns normalized schema array
	 *
	 * For example:
	 * - 'date-time' returns ['type' => 'string', 'format' => 'date-time']
	 * - 'bool' returns ['type' => 'boolean']
	 * - ['type' => 'date-time'] returns ['type' => 'string', 'format' => 'date-time']
	 *
	 * @param string|array $schema The type value or schema to normalize
	 * @return array The normalized schema
	 */
	public static function normalizeSchema( $schema ): array {
		// Handle string input (just a type value)
		if ( is_string( $schema ) ) {
			$schema = array( 'type' => $schema );
		}

		$typeToFormat = array(
			'date'      => 'date',
			'date-time' => 'date-time',
			'email'     => 'email',
			'hostname'  => 'hostname',
			'ipv4'      => 'ipv4',
			'uri'       => 'uri',
		);

		$replacements = array(
			'mixed' => 'string',
			'bool'  => 'boolean',
		);

		// Normalize 'type' field if present and format is not already set
		if ( isset( $schema['type'] ) && ! isset( $schema['format'] ) ) {
			$type = $schema['type'];

			if ( is_array( $type ) ) {
				// Handle array of types (e.g., ['string', 'null'])
				foreach ( $type as $key => $value ) {
					if ( isset( $typeToFormat[ $value ] ) ) {
						$schema['type'][ $key ] = 'string';
						if ( ! isset( $schema['format'] ) ) {
							$schema['format'] = $typeToFormat[ $value ];
						}
					} elseif ( isset( $replacements[ $value ] ) ) {
						$schema['type'][ $key ] = $replacements[ $value ];
					}
				}
			} elseif ( isset( $typeToFormat[ $type ] ) ) {
				$schema['type'] = 'string';
				$schema['format'] = $typeToFormat[ $type ];
			} elseif ( isset( $replacements[ $type ] ) ) {
				$schema['type'] = $replacements[ $type ];
			}
		}

		// Recursively normalize 'properties'
		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $key => $property ) {
				if ( is_array( $property ) ) {
					$schema['properties'][ $key ] = self::normalizeSchema( $property );
				}
			}
		}

		// Recursively normalize 'items'
		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$schema['items'] = self::normalizeSchema( $schema['items'] );
		}

		// Recursively normalize 'allOf', 'oneOf', 'anyOf'
		foreach ( array( 'allOf', 'oneOf', 'anyOf' ) as $combiner ) {
			if ( isset( $schema[ $combiner ] ) && is_array( $schema[ $combiner ] ) ) {
				foreach ( $schema[ $combiner ] as $key => $subSchema ) {
					if ( is_array( $subSchema ) ) {
						$schema[ $combiner ][ $key ] = self::normalizeSchema( $subSchema );
					}
				}
			}
		}

		// Recursively normalize 'additionalProperties' if it's a schema
		if ( isset( $schema['additionalProperties'] ) && is_array( $schema['additionalProperties'] ) ) {
			$schema['additionalProperties'] = self::normalizeSchema( $schema['additionalProperties'] );
		}

		return $schema;
	}

	public static function normalizeEnum( $enum ) {
		if ( is_array( $enum ) ) {
			return array_unique( array_values( $enum ) );
		}
		return $enum;
	}

	public static function normalizeSchemaTitle( $title ) {
		// Remove invalid characters for schema titles.
		// Only allow alphanumeric characters and underscores.
		$title = preg_replace( '/[^a-zA-Z0-9_-]/', '_', $title );
		// Ensure the title starts with an alphabetic character or underscore.
		if ( ! preg_match( '/^[a-zA-Z_]/', $title ) ) {
			$title = '_' . $title;
		}


		return strtolower( $title );
	}
}
