<?php

namespace WPOpenAPI\Filters;

use WPOpenAPI\Callback;
use WPOpenAPI\CallbackFinder;
use WPOpenAPI\Filters;
use WPOpenAPI\Spec\Path;
use WPOpenAPI\View;

class AddCallbackInfoToDescription {

	private CallbackFinder $callbackFinder;
	private View $view;

	public function __construct( Filters $hooks, View $view, array $routes ) {
		$this->callbackFinder = new CallbackFinder( $routes );

		$hooks->addPathFilter(
			function( Path $path ) {
				return $this->addCallbackInfo( $path );
			}
		);
		$this->view = $view;
	}

	private function addCallbackInfo( Path $path ): Path {
		/**
		 * @var Callback[] $callbacks
		 */
		$callbacks = $this->callbackFinder->find( $path->getOriginalPath() );
		foreach ( $path->getOperations() as $operation ) {
			$method = $operation->getMethod();
			if ( isset( $callbacks[ $method ] ) ) {
				$callback     = $callbacks[ $method ];
				$description  = $operation->getDescription();
				$description .= $this->view->render(
					array(
						'callbackType' => $callback->getCallableType(),
						'callable'     => $callback->getCallable(),
						'filepath'     => htmlentities( $callback->getFilepath() ),
					)
				);
				$operation->setDescription( $description );
			}
		}
		return $path;
	}
}
