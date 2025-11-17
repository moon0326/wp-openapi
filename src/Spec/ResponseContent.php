<?php

namespace WPOpenAPI\Spec;

class ResponseContent {

	private string $mediaType;
	/**
	 * @var array
	 */
	private array $schema;

	public function __construct( $mediaType, array $schema ) {
		$this->mediaType = $mediaType;
		$this->schema    = $schema;
	}

	public function getMediaType(): string {
		return $this->mediaType;
	}

	public function getSchema(): array {
		return $this->schema;
	}
}
