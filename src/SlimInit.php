<?php
	namespace Adepto\Slim3Init;

	use Slim\{
		Container,
		App
	};

	use Psr\Http\Message\{
		ServerRequestInterface,
		ResponseInterface
	};

	use Adepto\Slim3Init\Handlers\Route;
	use Adepto\Slim3Init\Exceptions\InvalidRouteException;

	/**
	 * SlimInit
	 * Slim initialization handling.
	 *
	 * @author  bluefirex
	 * @version 1.1
	 * @package as.adepto.slim-init
	 */
	class SlimInit {
		protected $container;
		protected $exceptions;
		protected $handlers;
		protected $middleware;
		protected $app;

		/**
		 * Create a SlimInit container.
		 *
		 * This also auto-adds some exceptions:
		 *     - InvalidRequestException: 400
		 *     - UnauthorizedException: 401
		 *     - AccessDeniedException: 403
		 */
		public function __construct() {
			$scope = $this;

			$this->exceptions = [];
			$this->handlers = [];
			$this->middleware = [];

			$this->container = new Container([
				'settings'	=>	[
					'displayErrorDetails'	=>	true
				]
			]);

			/*
				Quick info on the handlers:
				We could just return [$scope, 'whateverHandler'] but then we'd have to make
				the handler methods public because Slim is calling them from outside (obviously).

				That's why we're doing things a bit more complex-looking.
			 */
			
			$this->container['errorHandler'] = function($c) use($scope) {
				return function($req, $res, $t) use($scope) {
					return $scope->handleError($req, $res, $t);
				};
			};

			$this->container['phpErrorHandler'] = function($c) use($scope) {
				return function($req, $res, $t) use($scope) {
					return $scope->handleError($req, $res, $t);
				};
			};

			$this->container['notFoundHandler'] = function($c) use($scope) {
				return function($req, $res) use($scope) {
					return $scope->handleNotFound($req, $res);
				};
			};

			$this->container['notAllowedHandler'] = function($c) use($scope) {
				return function ($req, $res, $methods) use($scope) {
					return $scope->handleMethodNotAllowed($req, $res, $methods);
				};
			};

			/*
				Add some default exceptions
			 */
			$this->setException('Adepto\\SlimInit\\Exceptions\\InvalidRequestException', 400);
			$this->setException('Adepto\\SlimInit\\Exceptions\\UnauthorizedException', 401);
			$this->setException('Adepto\\SlimInit\\Exceptions\\AccessDeniedException', 403);

			/*
				Set an empty debug header (disabling this feature essentially)
			 */
			$this->setDebugHeader('', '');
		}

		/**
		 * Get the Slim container.
		 *
		 * @return Slim\Container
		 */
		public function getContainer(): Container {
			return $this->container;
		}

		/**
		 * Set the header used for debugging.
		 * If a header is set with the key and value defined here,
		 * it will circumvent any "human friendly" error page and output exception's
		 * details in JSON, so be careful with this.
		 *
		 * @param string $header        Header's Name
		 * @param string $expectedValue Header's Value to trigger debugging
		 */
		public function setDebugHeader(string $header, string $expectedValue = ''): SlimInit {
			if (empty($header)) {
				unset($this->container['debugHeader']);
			} else {
				$this->container['debugHeader'] = [
					'key'		=>	$header,
					'value'		=>	$expectedValue 
				];
			}

			return $this;
		}

		/**
		 * Set the status code for an exception.
		 *
		 * @param string|array $ex         Exception Class(es)
		 * @param int    $statusCode HTTP status code
		 */
		public function setException($ex, $statusCode): SlimInit {
			if (is_array($ex)) {
				foreach ($ex as $e) {
					$this->setException($e, $statusCode);
				}

				return $this;
			}

			$this->exceptions[$ex] = $statusCode;

			return $this;
		}

		/**
		 * Add an item to the container of this slim app.
		 *
		 * @param string $key   Key
		 * @param mixed  $value Value
		 */
		public function addToContainer($key, $value): SlimInit {
			$this->container[$key] = $value;

			return $this;
		}

		/**
		 * Add a single handler class.
		 * That class MUST implement getRoutes().
		 * The class will NOT be automatically loaded unless an autoloader is defined
		 * for that class.
		 *
		 * @param string $className Class Name
		 */
		public function addHandler($className): SlimInit {
			$this->handlers[$className] = $className::getRoutes();

			return $this;
		}

		/**
		 * Collect all php files form a directory and put them up as handlers.
		 * This automatically loads them as well.
		 *
		 * @param string $dir Directory to look in - NO trailing slash!
		 */
		public function addHandlersFromDirectory($dir): SlimInit {
			if (!is_dir($dir)) {
				throw new \InvalidArgumentException('Could not find directory: ' . $dir);
			}

			$handlerFiles = glob($dir . '/*.php');
			$handlers = [];

			foreach ($handlerFiles as $handlerFile) {
				require_once $handlerFile;

				$handlerClass = str_replace('.php', '', basename($handlerFile));
				$this->addHandler($handlerClass);
			}

			return $this;
		}

		/**
		 * Add slim-compatible middleware.
		 *
		 * @param callable $middleware Middleware to add
		 */
		public function addMiddleware(callable $middleware): SlimInit {
			$this->middleware[] = $middleware;

			return $this;
		}

		/**
		 * Boot up the slim application, add handlers, exceptions and run it.
		 *
		 * @return Slim\App
		 */
		public function run(): App {
			$scope = $this;

			$this->app = new App($this->container);

			// Map the routes from all loaded handlers
			$this->app->group('', function() use($scope) {
				$instances = [];

				foreach ($scope->handlers as $handlerClass => $handlerConfig) {
					if (!isset($instances[$handlerClass])) {
						$instances[$handlerClass] = new $handlerClass($scope->container);
					}

					/** @var $config Handlers\Route */
					foreach ($handlerConfig as $route) {
						if (!$route instanceof Route) {
							throw new \InvalidArgumentException('Route must be instance of Adepto\\Slim3Init\\Handlers\\Route');
						}

						$slimRoute = $this->map([ $route->getHTTPMethod() ], $route->getURL(), function($request, $response, $args) use($handlerClass, $route, $instances) {
							$method = $route->getClassMethod();
							$argsObject = self::arrayToObject($args);

							foreach ($route->getArguments() as $key => $value) {
								$argsObject->$key = $value;
							}

							if (!is_callable([ $instances[$handlerClass], $method ])) {
								throw new InvalidRouteException($handlerClass . ' defines a route "' . $route->getURL() . '"" for which the handler "' . $route->getClassMethod() . '" is not callable', 1);
							}

							return $instances[$handlerClass]->onRequest($request, $response, $argsObject, [ $instances[$handlerClass], $method ]);
						});

						if (!empty($route->getName())) {
							$slimRoute->setName($route->getName());
						}
					}
				}
			});

			// Add all middleware callables
			foreach ($this->middleware as $middleware) {
				$this->app->add($middleware);
			}

			$this->app->run();

			return $this->app;
		}

		/***********
		 * HELPERS *
		 ***********/

		/**
		 * Convert an array to an object. This deep-copies everything
		 * from the array to the object.
		 * Note: The object is a reference. If you return this from a method
		 * any other function can modify it!
		 *
		 * @param array  $arr Array to convert
		 * 
		 * @return stdClass
		 */
		protected static function arrayToObject(array $arr): \stdClass {
			$obj = new \stdClass();

			foreach ($arr as $key => $val) {
				if (is_array($val)) {
					$obj->$key = self::arrayToObject($val);
				} else {
					$obj->$key = $val;
				}
			}

			return $obj;
		}

		/************
		 * HANDLERS *
		 ************/

		protected function handleError(ServerRequestInterface $req, ResponseInterface $res, \Throwable $t): ResponseInterface {
			$headers = $req->getHeaders();
			$tClass = get_class($t);
			$statusCode = $this->exceptions[$tClass] ?? 500;

			$content = [
				'status'		=>	'error',
				'message'		=>	$t->getMessage()
			];

			if ($t->getCode() && $statusCode != 500) {
				$content['code'] = $t->getCode();
			}

			/*
				Internal errors get more info for developers but less errors for users.
			 */
			if ($statusCode == 500) {
				$content['message'] = 'An internal error happened. >.<';

				if (isset($this->container['debugHeader'])) {
					$debugHeader = $this->container['debugHeader'];

					if ($req->hasHeader($debugHeader['key']) && $req->getHeader($debugHeader['key'])[0] == $debugHeader['value']) {
						$content['details'] = [
							'exception'		=>	get_class($t),
							'message'		=>	$t->getMessage(),
							'stacktrace'	=>	explode("\n", $t->getTraceAsString())
						];
					}
				}
			}

			return $res->withJson($content, $statusCode);
		}

		protected function handleNotFound(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface {
			return $res->withJson([
				'status'		=>	'error',
				'message'		=>	'Page not found.'
			], 404);
		}

		protected function handleMethodNotAllowed(ServerRequestInterface $req, ResponseInterface $res, array $methods): ResponseInterface {
			$res = $res->withJson([
				'status'			=>	'error',
				'message'			=>	'Method not allowed',
				'allowedMethods'	=>	$methods
			], 405);

			$res = $res->withHeader('Allow', implode(', ', $methods));

			return $res;
		}
	}