<?php

namespace WPOpenAPI\Spec;

class Info {

	private string $title;
	private string $version;
	private ?string $summary = null;
	private string $description;
	private ?Contact $contact = null;
	private ?License $license = null;
	private ?string $termsOfService = null;

	public function __construct( string $title, string $version, string $description, Contact $contact ) {
		$this->title       = $title;
		$this->description = $description;
		$this->version     = $version;
		$this->contact     = $contact;
	}

	public function getContact(): Contact
	{
	    return $this->contact;
	}

	/**
	 * @param string $summary A short summary of the API.
	 *
	 * @return void
	 */
	public function setSummary(string $summary)
	{
	    $this->summary = $summary;
	}

	public function getSummary(): string
	{
	    return $this->summary;
	}

	/**
	 * @param License $license The license information for the exposed API.
	 *
	 * @return void
	 */
	public function setLicense(License  $license) {
	    $this->license = $license;
	}

	public function getLicense(): License
	{
	    return $this->license;
	}

	/**
	 * @param string $termsOfServiceUrl A URL to the Terms of Service for the API. This MUST be in the form of a URL.
	 *
	 * @return void
	 */
	public function setTermsOfService(string $termsOfServiceUrl)
	{
	    $this->termsOfService = $termsOfServiceUrl;
	}

	public function getTermsOfService(): string
	{
	    return $this->termsOfService;
	}

	public function toArray(): array {
		 $data = array(
			'title'       => $this->title,
			'description' => $this->description,
			'version'     => $this->version,
		);

		 if ($this->summary) {
			 $data['summary'] = $this->summary;
		 }

		 if ($this->termsOfService) {
			 $data['termsOfService'] = $this->termsOfService;
		 }

		 if ($this->contact) {
			 $data['contact'] = $this->contact->toArray();
		 }

		 if ($this->license) {
			 $data['license'] = $this->license->toArray();
		 }

		 return $data;
	}
}
