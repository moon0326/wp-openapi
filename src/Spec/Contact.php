<?php

namespace WPOpenAPI\Spec;

class Contact {
	private string $email;

	public function __construct( string $email ) {
		$this->email = $email;
	}

	public function toArray(): array {
		return array(
			'email' => $this->email,
		);
	}
}
