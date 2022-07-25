<?php
namespace WPOpenAPI\Spec;

class Response {
	private int $code;
	private string $description;

	/**
	 * @var ResponseContent[]
	 */
	private array $contents = array();

	public function __construct( int $code, string $description ) {
		$this->code        = $code;
		$this->description = $description;
	}

	public function getCode(): int {
		return $this->code;
	}

	public function addContent( ResponseContent $content ) {
		$this->contents[] = $content;
	}

	public function toArray(): array {
		$data = array(
			'description' => $this->description,
		);

		if ( count( $this->contents ) ) {
			foreach ( $this->contents as $content ) {
				$data['content'][ $content->getMediaType() ]['schema'] = $content->getSchema();
			}
		}

		return $data;
	}
}
