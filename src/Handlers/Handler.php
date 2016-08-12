<?php
	namespace Adepto\Slim3Init\Handlers;
	
	use Interop\Container\ContainerInterface;

	/**
	 * Handler
	 * An abstract class describing an API-like handler.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.slim-init.handlers
	 */
	abstract class Handler {
		protected $container;

		/**
		 * Create a handler with a Slim container.
		 *
		 * @param ContainerInterface $container
		 */
		public function __construct(ContainerInterface $container) {
			$this->container = $container;
		}

		/**
		 * Get the routes for this handler. This has to be an array
		 * full of {@see Route} objects.
		 *
		 * @return array
		 */
		public static function getRoutes(): array {
			return [];
		}
	}