<?php
	namespace Adepto\Slim3Init\Handlers;
	
	use Interop\Container\ContainerInterface;

	use Psr\Http\Message\{
		ServerRequestInterface,
		ResponseInterface
	};

	/**
	 * Handler
	 * An abstract class describing an API-like handler.
	 *
	 * @author  bluefirex
	 * @version 1.2
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
		 * Get the container
		 *
		 * @return Interop\Container\ContainerInterface
		 */
		public function getContainer(): ContainerInterface {
			return $this->container;
		}

		/**
		 * Get the path for a named route.
		 * This works with all handlers, not just in this handler.
		 *
		 * @param  string $name      Name of the Route
		 * @param  array  $arguments Additional parameters/args
		 *
		 * @return string
		 */
		public function getPathFor(string $name, array $arguments = []) {
			return $this->getContainer()->get('router')->pathFor($name, $arguments);
		}

		/**
		 * Do something before the request is actually processed by $next (which is your handler's defined function).
		 * Do NOT forget to call $next($request, $response, $args) when overriding this!!
		 *
		 * @param  ServerRequestInterface $request  Slim Request
		 * @param  ResponseInterface      $response Slim Response
		 * @param  \stdClass              $args     Arguments
		 * @param  callable               $next     Your handler's defined function
		 *
		 * @return ResponseInterface
		 */
		public function onRequest(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args, callable $next): ResponseInterface {
			return $next($request, $response, $args);
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