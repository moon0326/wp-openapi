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
	 * In WordPress, some schema formats are not compatible with the OpenAPI specification.
	 * This function converts those special or non-standard types to OpenAPI-compatible values.
	 * These values should not be used as 'type' values according to JSON Schema,
	 * but are sometimes used in WordPress REST API schemas regardless.
	*/
	public static function normalzieInvalidType( $type ) {
		if ( is_array( $type ) ) {
			foreach ( $type as $key => $value ) {
				$type[ $key ] = self::normalzieInvalidType( $value );
			}
			return $type;
		}

		$replacements = array(
			'date' => 'string',
			'date-time' => 'string',
			'email' => 'string',
			'hostname' => 'string',
			'ipv4' => 'string',
			'uri' => 'string',
			'mixed' => 'string',
			'bool' => 'boolean',
		);

		if ( isset( $replacements[ $type ] ) ) {
			return $replacements[ $type ];
		}

		return $type;
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
		$title = preg_replace( '/[^a-zA-Z0-9_]/', '_', $title );
		// Ensure the title starts with an alphabetic character or underscore.
		if ( ! preg_match( '/^[a-zA-Z_]/', $title ) ) {
			$title = '_' . $title;
		}


		return strtolower( $title );
	}
}
