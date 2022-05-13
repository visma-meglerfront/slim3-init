<?php
	namespace Adepto\Slim3Init\Handlers;

	use Adepto\Slim3Init\{
		Container,
		Request,
		Response
	};

	use Psr\Container\{
		ContainerExceptionInterface,
		NotFoundExceptionInterface
	};

	use ReflectionClass;
	use ReflectionMethod;
	use Slim\Interfaces\RouteParserInterface;
	use RuntimeException;
	use stdClass;

	use Adepto\Slim3Init\Attributes\Route as RouteAttribute;

	/**
	 * Handler
	 * An abstract class describing an API-like handler.
	 *
	 * @author  bluefirex
	 * @version 1.2
	 */
	abstract class Handler {
		protected Container $container;

		/**
		 * Create a handler with a Slim container.
		 *
		 * @param Container $container
		 */
		public function __construct(Container $container) {
			$this->container = $container;
		}

		/**
		 * Get the container
		 *
		 * @return Container
		 */
		public function getContainer(): Container {
			return $this->container;
		}

		/**
		 * Get the path for a named route.
		 * This works with all handlers, not just in this handler.
		 *
		 * @param string $name      Name of the Route
		 * @param array  $arguments Additional parameters/args
		 *
		 * @return string
		 *
		 * @throws NotFoundExceptionInterface   If router was not found
		 * @throws RuntimeException             If route was not found
		 * @throws ContainerExceptionInterface  If there was an error with the container
		 */
		public function getPathFor(string $name, array $arguments = []): string {
			/** @var RouteParserInterface $router */
			$router = $this->getContainer()->get('router');

			return $router->urlFor($name, $arguments);
		}

		/**
		 * Do something before the request is actually processed by $next (which is your handler's defined function).
		 * Do NOT forget to call $next($request, $response, $args) when overriding this!!
		 *
		 * @param Request  $request  Slim Request
		 * @param Response $response Slim Response
		 * @param stdClass $args     Arguments
		 * @param callable $next     Your handler's defined function
		 *
		 * @return Response
		 */
		public function onRequest(Request $request, Response $response, stdClass $args, callable $next): Response {
			return $next($request, $response, $args);
		}

		/**
		 * Get the routes for this handler. This has to be an array
		 * full of {@see Route} objects, either by using {@see Route} attributes or overriding this method
		 *
		 * @return Route[]
		 */
		public static function getRoutes(): array {
			$reflection = new ReflectionClass(static::class);
			$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
			$routes = [];

			foreach ($methods as $method) {
				$routeAttributes = $method->getAttributes(RouteAttribute::class);

				foreach ($routeAttributes as $routeAttribute) {
					/** @var RouteAttribute $attribute */
					$attribute = $routeAttribute->newInstance();
					$routes[] = Route::fromAttribute($attribute, $method->getName());
				}
			}

			return $routes;
		}
	}