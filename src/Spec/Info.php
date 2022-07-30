<?php

namespace WPOpenAPI\Spec;

class Info {

	private string $title;
	private string $description;
	private string $version;
	private Contact $contact;

	public function __construct( string $title, string $version, string $description, Contact $contact ) {
		$this->title       = $title;
		$this->description = $description;
		$this->version     = $version;
		$this->contact     = $contact;
	}

	public function toArray(): array {
		return array(
			'title'       => $this->title,
			'description' => $this->description,
			'version'     => $this->version,
			'contact'     => $this->contact->toArray(),
		);
	}
}
