<?php

namespace WPOpenAPI;

class Callback {

	private array $callable;
	private ?string $filepath;

	public function __construct( array $callable, string $filepath = null ) {
		$this->callable = $callable;
		$this->filepath = $filepath;
	}

	public function getCallableType(): string {
		if ( count( $this->callable ) === 1 ) {
			return 'function';
		}

		return 'class';
	}

	public function getCallable(): array {
		return $this->callable;
	}

	public function getFilepath(): ?string {
		return $this->filepath;
	}
}
