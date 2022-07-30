<?php

namespace WPOpenAPI\Spec;

class Contact {
	private string $email;
	private string $name;
	private string $url;

	public function __construct( string $name, string $url, string $email ) {
		$this->email = $email;
		$this->name  = $name;
		$this->url   = $url;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param string $email
	 */
	public function setEmail( string $email ): void {
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getName() {
		 return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName( string $name ): void {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @param string $url
	 */
	public function setUrl( string $url ): void {
		$this->url = $url;
	}

	public function toArray(): array {
		return array(
			'name'  => $this->name,
			'url'   => $this->url,
			'email' => $this->email,
		);
	}
}
