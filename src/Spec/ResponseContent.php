<?php

namespace WPOpenAPI\Spec;

class ResponseContent {

	private string $mediaType;
	/**
	 * @var array
	 */
	private array $schema;
	private $example = null;

	public function __construct( $mediaType, array $schema, $example = null ) {
		$this->mediaType = $mediaType;
		$this->schema    = $schema;
		$this->example   = $example;
	}

	public function getMediaType(): string {
		return $this->mediaType;
	}

	public function getSchema(): array {
		return $this->schema;
	}

	public function getExample() {
		return $this->example;
	}
}
