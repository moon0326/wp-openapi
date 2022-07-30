<?php

namespace WPOpenAPI\Spec;

class License {

	/**
	 * REQUIRED. The license name used for the API.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * An SPDX license expression for the API. The identifier field is mutually exclusive of the url field.
	 * Refer to https://spdx.dev/spdx-specification-21-web-version/#h.jxpfx0ykyb60 for more info on SPDX.
	 *
	 * @var string|null
	 */

	private ?string $identifier;

	/**
	 * A URL to the license used for the API. This MUST be in the form of a URL.
	 * The url field is mutually exclusive of the identifier field.
	 *
	 * @var string|null
	 */
	private ?string $url;

	public function __construct( string $name, string $identifier = null, string $url = null ) {
		$this->name       = $name;
		$this->identifier = $identifier;
		$this->url        = $url;
	}


	/**
	 * @param string $identifier
	 * @return void
	 */
	public function setIdentifier( string $identifier ) {
		$this->identifier = $identifier;
	}

	public function setUrl( string $url ) {
		$this->url = $url;
	}

	/**
	 * @return string[]
	 */
	public function toArray(): array
	{
	     $data = array(
			'name' => $this->name,
		);

		 if ($this->url) {
			 $data['url'] = $this->url;
		 }

		 if ($this->identifier) {
			 $data['identifier'] = $this->identifier;
		 }

		 return $data;
	}
}
