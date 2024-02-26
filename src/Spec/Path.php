<?php
namespace WPOpenAPI\Spec;

class Path {
	/**
	 * @var string
	 */
	private string $path;

	/**
	 * @var Operation[]
	 */
	private array $operations = array();
	private string $originalPath;
	private array $pathVariables = array();
	private ?string $schemaRef;

	public function __construct(
		string $path,
		string $schemaRef = null
	) {
		$this->originalPath = $path;
		$this->path         = $this->replacePathVariable( $path );
		if ( $schemaRef ) {
			$this->schemaRef = '#/components/schemas/' . $schemaRef;
		}
	}

	public function replacePathVariable( string $path ): string {
		if ( str_contains( $path, '(?P<' ) ) {
			$path = trim( $path, '?' );
			$path = preg_replace_callback(
				'/\(.*?<([^<>]*)>.*?\)(?=\/|$|\+)/',
				function ( $match ) {
					$this->pathVariables[] = $match[1];
					return '{' . $match[1] . '}';
				},
				$path
			);
			// it's possible that the path still have unwanted chars left
			// after the preg replacement. Clean them up.
			// @todo -- find a better regex that works for all
			 $path = str_replace( array( '(', ')', '?' ), '', $path );
		}

		return $path;
	}

	public function getOperations(): array {
		return $this->operations;
	}

	public function generateOperationsFromRouteArgs( $args ): void {
		foreach ( $args as $arg ) {
			$responses = array();
			if ( ! empty( $this->schemaRef ) ) {
				$content  = new ResponseContent(
					'application/json',
					array(
						'$ref' => $this->schemaRef,
					)
				);
				$response = new Response( 200, 'OK' );
				$response->addContent( $content );
				$responses[] = $response;

			} else {
				$responses[] = new Response( 200, 'OK' );
			}

			foreach ( $arg['methods'] as $method => $value ) {
				$description = $arg['description'] ?? '';
				$method      = strtolower( $method );
				if($method == 'options') continue;
				$op          = new Operation( $method, $responses );
				$op->setDescription( $description );
				$op->generateParametersFromRouteArgs( $method, $arg['args'], $this->pathVariables, $this->path );
				$this->operations[] = $op;
			}
		}
	}

	public function getOriginalPath(): string {
		return $this->originalPath;
	}

	public function getPath(): string {
		return $this->path;
	}

	public function toArray(): array {
		$data = array();
		foreach ( $this->operations as $operation ) {
			$data[ $operation->getMethod() ] = $operation->toArray();
		}

		return $data;
	}
}
