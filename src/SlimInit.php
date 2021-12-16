<?php
	namespace Adepto\Slim3Init;

	use Adepto\Slim3Init\Factories\SlimInitPsr17Factory;

	use Adepto\Slim3Init\Exceptions\{
		AccessDeniedException,
		InternalErrorException,
		InvalidRequestException,
		InvalidRouteException,
		MethodNotAllowedException,
		NotFoundException,
		UnauthorizedException,
		InvalidExceptionHandlerException
	};

	use Adepto\Slim3Init\Middleware\CORS;
	use Adepto\Slim3Init\Handlers\{
		ExceptionHandler,
		MethodNotAllowedExceptionHandler,
		NotFoundExceptionHandler,
		Route,
		ShutdownHandler
	};

	use Psr\Http\Message\ServerRequestInterface;
	use Slim\App;

	use Slim\Exception\{
		HttpMethodNotAllowedException,
		HttpNotFoundException
	};

	use Slim\Factory\AppFactory;
	use Slim\Factory\Psr17\Psr17FactoryProvider;
	use Slim\Factory\ServerRequestCreatorFactory;

	use stdClass;
	use Throwable;
	use InvalidArgumentException;
	use ReflectionClass;
	use ReflectionException;

	/**
	 * SlimInit
	 * Slim initialization handling.
	 *
	 * @author  bluefirex
	 * @version 2.0
	 * @package as.adepto.slim-init
	 */
	class SlimInit {
		/** @var Container Container for passing around to everything */
		protected $container;
		/** @var array Exception mapping to status codes and handlers */
		protected $exceptions;
		/** @var array Route handlers */
		protected $handlers;
		/** @var array User-added middleware */
		protected $middleware;
		/** @var callable[] */
		protected $exceptionCallbacks;
		/** @var ?CORS */
		protected $corsMiddleware;
		/** @var App Actual Slim instance */
		protected $app;
		/** @var ExceptionHandler Cached default exception handler */
		private $defaultExceptionHandler;

		/**
		 * Create a SlimInit container.
		 *
		 * This also auto-adds some exceptions:
		 *     - InvalidRequestException: 400
		 *     - UnauthorizedException: 401
		 *     - AccessDeniedException: 403
		 *     - NotFoundException: 404
		 *     - MethodNotAllowedException: 405
		 */
		public function __construct(bool $withCORS = false) {
			$this->exceptions = [];
			$this->handlers = [];
			$this->middleware = [];
			$this->exceptionCallbacks = [];
			$this->container = new Container();

			AppFactory::setContainer($this->container);
			Psr17FactoryProvider::addFactory(SlimInitPsr17Factory::class);

			$this->app = AppFactory::create();

			// Register PHP errors
			$this->registerShutdownHandler();

			// Add body parsing
			$this->app->addBodyParsingMiddleware();

			// Add CORS middleware if requested
			if ($withCORS) {
				$this->corsMiddleware = new CORS($this->container, ['*']);
				$this->app->add($this->corsMiddleware);
			}

			// Add routing
			$this->app->addRoutingMiddleware();

			// Set router for handlers to access
			$this->container->set('router', $this->app->getRouteCollector()->getRouteParser());
			$this->container->set('route_collector', $this->app->getRouteCollector());

			// Add some default exceptions
			$this->setException(InvalidRequestException::class, 400);
			$this->setException(UnauthorizedException::class, 401);
			$this->setException(AccessDeniedException::class, 403);
			$this->setException(NotFoundException::class, NotFoundExceptionHandler::class);
			$this->setException(MethodNotAllowedException::class, MethodNotAllowedExceptionHandler::class);

			// Set an empty debug header (disabling this feature)
			$this->setDebugHeader(null);
		}

		/**
		 * Get the Slim container.
		 *
		 * @return Container
		 */
		public function getContainer(): Container {
			return $this->container;
		}

		/**
		 * Get the CORS middleware, if requested during construction
		 *
		 * @return CORS|null
		 */
		public function getCORS(): ?CORS {
			return $this->corsMiddleware;
		}

		/**
		 * Set the base path. This is required if Slim is running on a path that is not "/"
		 *
		 * @param string $path
		 *
		 * @return $this
		 */
		public function setBasePath(string $path): self {
			$this->app->setBasePath($path);

			return $this;
		}

		/**
		 * Set the header used for debugging.
		 * If a header is set with the key and value defined here,
		 * it will circumvent any "human friendly" error page and output exception's
		 * details in JSON, so be careful with this.
		 *
		 * @param ?string   $header        Header's Name
		 * @param string    $expectedValue Header's Value to trigger debugging
		 */
		public function setDebugHeader(?string $header, string $expectedValue = ''): SlimInit {
			if (empty($header)) {
				$this->container->set('debugHeader', null);
			} else {
				$this->container->set('debugHeader', [
					'key'		=>	$header,
					'value'		=>	$expectedValue
				]);
			}

			return $this;
		}

		/**
		 * Set the status code for an exception.
		 *
		 * @param string|array                  $ex                     Exception Class(es)
		 * @param int|string                    $statusCodeOrHandler    HTTP status code
		 */
		public function setException($ex, $statusCodeOrHandler): SlimInit {
			if (is_array($ex)) {
				foreach ($ex as $e) {
					$this->setException($e, $statusCodeOrHandler);
				}
			} else {
				$this->exceptions[$ex] = $statusCodeOrHandler;
			}

			return $this;
		}

		/**
		 * Get the handler for a specific exception
		 *
		 * @param Throwable $t  Exception to get handler for
		 *
		 * @return ExceptionHandler Exception handler instance
		 *
		 * @throws InvalidExceptionHandlerException If the requested handler does not extend {@link ExceptionHandler}
		 */
		public function getHandlerForException(Throwable $t): ExceptionHandler {
			$class = get_class($t);

			if (is_string($this->exceptions[$class] ?? null) && class_exists($this->exceptions[$class])) {
				$handlerClass = $this->exceptions[$class];
				$parentClasses = class_parents($handlerClass);

				if ($handlerClass != ExceptionHandler::class && !in_array(ExceptionHandler::class, $parentClasses)) {
					throw new InvalidExceptionHandlerException('Exception handler "' . $handlerClass . '" must extend "' . ExceptionHandler::class . '"');
				}

				return new $handlerClass($this->container, $this, null, false);
			}

			return $this->getDefaultExceptionHandler();
		}

		/**
		 * Get the status code for an exception
		 * Returns 500 for unknown or custom handlers
		 *
		 * @param Throwable $t
		 *
		 * @return int
		 */
		public function getStatusCodeForException(Throwable $t): int {
			$class = get_class($t);

			if (is_numeric($this->exceptions[$class] ?? null)) {
				return $this->exceptions[$class];
			}

			return 500;
		}

		/**
		 * Get the default exception handler
		 *
		 * @return ExceptionHandler
		 */
		public function getDefaultExceptionHandler(): ExceptionHandler {
			if ($this->defaultExceptionHandler === null) {
				$this->defaultExceptionHandler = new ExceptionHandler($this->container, $this, null, true);
			}

			return $this->defaultExceptionHandler;
		}

		/**
		 * Add an item to the container of this slim app.
		 *
		 * @param string $key   Key
		 * @param mixed  $value Value
		 */
		public function addToContainer(string $key, $value): SlimInit {
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
		public function addHandler(string $className): SlimInit {
			if (!class_exists($className)) {
				throw new InvalidArgumentException('Could not find class ' . $className);
			}

			$this->handlers[$className] = $className::getRoutes();

			return $this;
		}

		/**
		 * Collect all php files form a directory and put them up as handlers.
		 * This automatically loads them as well.
		 * Does not work with namespaced classes.
		 *
		 * @param string $dir Directory to look in - NO trailing slash!
		 */
		public function addHandlersFromDirectory(string $dir): SlimInit {
			if (!is_dir($dir)) {
				throw new InvalidArgumentException('Could not find directory: ' . $dir);
			}

			$handlerFiles = glob($dir . '/*.php');

			foreach ($handlerFiles as $handlerFile) {
				/** @noinspection PhpIncludeInspection */
				require_once $handlerFile;

				$handlerClass = str_replace('.php', '', basename($handlerFile));

				try {
					$reflectionClass = new ReflectionClass($handlerClass);

					if (!$reflectionClass->isAbstract()) {
						$this->addHandler($handlerClass);
					}
				} catch (ReflectionException $e) {
					throw new InvalidArgumentException('Handler class "' . $handlerClass . '" could not be found in ' . $handlerFile, 500, $e);
				}
			}

			return $this;
		}

		/**
		 * Collect all php files from a namespace and put them up as handlers.
		 * Automatically ignores abstract classes.
		 *
		 * Example:
		 * 		$namespace = Adepto\Slim3Init\Handlers
		 * 		$prefix = Adepto\Slim3Init\
		 * 		$directory = /htdocs/adepto-slim3init/src
		 *
		 * @param string $namespace Namespace to add, do not end with a backslash!
		 * @param string $prefix    What is the prefix of this namespace? Include trailing backslash! (works just like in composer)
		 * @param string $directory How is the prefix mapped to the filesystem? Do not end with a slash!
		 */
		public function addPsr4Namespace(string $namespace, string $prefix, string $directory): SlimInit {
			$remainingNamespace = str_replace($prefix, '', $namespace);
			$dirPath = str_replace('\\', DIRECTORY_SEPARATOR, $directory . '/' . $remainingNamespace);

			$handlerFiles = glob($dirPath . '/*.php');

			foreach ($handlerFiles as $handlerFile) {
				/** @noinspection PhpIncludeInspection */
				require_once $handlerFile;

				$handlerClass = str_replace('.php', '', basename($handlerFile));
				$handlerClassPath = $prefix . str_replace(DIRECTORY_SEPARATOR, '\\', $remainingNamespace . '\\' . $handlerClass);

				if (!class_exists($handlerClassPath)) {
					throw new InvalidArgumentException('Could not find class "' . $handlerClassPath . '" in ' . $handlerFile);
				}

				try {
					$reflectionClass = new ReflectionClass($handlerClassPath);

					if (!$reflectionClass->isAbstract()) {
						$this->addHandler($handlerClassPath);
					}
				} catch (ReflectionException $exception) {
					throw new InvalidArgumentException($handlerClass . ' is not a valid class');
				}
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
		 * Set all slim-compatible middlewares.
		 *
		 * @param array $middleware
		 *
		 * @return $this
		 */
		public function setMiddleware(array $middleware): SlimInit {
			$this->middleware = $middleware;

			return $this;
		}

		/**
		 * Add a callback to run when an unmapped exception occurs.
		 * Receives the {@see Request} and the Throwable as an argument.
		 *
		 * @param callable $callback
		 *
		 * @return $this
		 */
		public function addExceptionCallback(callable $callback): SlimInit {
			$this->exceptionCallbacks[] = $callback;

			return $this;
		}

		/**
		 * Set all callbacks to run when an unmapped exception occurs.
		 * Receives the {@see Request} and the Throwable as an argument.
		 *
		 * @param array $callbacks
		 *
		 * @return $this
		 */
		public function setExceptionCallbacks(array $callbacks): SlimInit {
			$this->exceptionCallbacks = $callbacks;

			return $this;
		}

		protected function registerShutdownHandler() {
			$serverRequestCreator = ServerRequestCreatorFactory::create();
			$request = $serverRequestCreator->createServerRequestFromGlobals();
			$shutdownHandler = new ShutdownHandler(Request::fromSlimRequest($request), $this->getDefaultExceptionHandler(), true, true, true);

			register_shutdown_function($shutdownHandler);
		}

		/**
		 * Boot up the slim application, add handlers, exceptions and run it.
		 *
		 * @return App
		 */
		public function run(): App {
			$scope = $this;

			// Map the routes from all loaded handlers
			$instances = [];

			// Already mapped OPTIONS
			$optionsMapped = [];

			foreach ($scope->handlers as $handlerClass => $handlerConfig) {
				if (!isset($instances[$handlerClass])) {
					$instances[$handlerClass] = new $handlerClass($scope->container);
				}

				/** @var $config Handlers\Route */
				foreach ($handlerConfig as $route) {
					if (!$route instanceof Route) {
						throw new InvalidArgumentException('Route must be instance of Adepto\\Slim3Init\\Handlers\\Route');
					}

					$slimRoute = $this->app->map([ $route->getHTTPMethod() ], $route->getURL(), function($request, $response, $args) use($handlerClass, $route, $instances) {
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

					if ($this->corsMiddleware && !isset($optionsMapped[$route->getURL()])) {
						$this->app->map([ 'OPTIONS' ], $route->getURL(), function($request, $response) {
							return $response;
						});

						$optionsMapped[$route->getURL()] = true;
					}
				}
			}

			// Add all middleware callables
			foreach ($this->middleware as $middleware) {
				$this->app->add($middleware);
			}

			$scope = $this;

			// Add error handlers
			$errorMiddleware = $this->app->addErrorMiddleware(false, true, true);

			// 404
			$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function(ServerRequestInterface $request, Throwable $exception) use ($scope) {
				$handler = $scope->getHandlerForException(new NotFoundException($exception->getMessage(), $exception->getCode(), $exception));
				return $handler->handle(Request::fromSlimRequest($request), $exception, false);
			});

			// 405
			$errorMiddleware->setErrorHandler(HttpMethodNotAllowedException::class, function(ServerRequestInterface $request, Throwable $exception) use ($scope) {
				$handler = $scope->getHandlerForException(new MethodNotAllowedException($exception->getMessage(), $exception->getCode(), $exception));
				return $handler->handle(Request::fromSlimRequest($request), $exception, false);
			});

			// 500 or anything else
			$errorHandler = function(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors) use ($scope) {
				$request = Request::fromSlimRequest($request);

				// Find and run the exception handler
				$handler = $scope->getHandlerForException($exception);

				// If default handler, try to find a general customization
				if (get_class($handler) == ExceptionHandler::class) {
					$handler = $scope->getHandlerForException(new InternalErrorException($request, $exception->getMessage(), $exception));
				}

				$handler->setLogException($logErrors);

				// Enable error details if debug header is set
				if ($scope->isDebug($request)) {
					$displayErrorDetails = true;
				}

				// Go!
				$response = $handler->handle($request, $exception, $displayErrorDetails);

				if ($response->getStatusCode() >= 500) {
					// Run exception callbacks
					foreach ($scope->exceptionCallbacks as $callback) {
						$callback($request, $exception);
					}
				}

				return $response;
			};

			$errorMiddleware->setDefaultErrorHandler($errorHandler);

			$this->app->run();

			return $this->app;
		}

		/***********
		 * HELPERS *
		 ***********/

		protected function isDebug(Request $request): bool {
			if ($this->container->has('debugHeader')) {
				$debugHeader = $this->container['debugHeader'];

				if ($debugHeader && $request->hasHeader($debugHeader['key']) && $request->getHeader($debugHeader['key'])[0] == $debugHeader['value']) {
					return true;
				}
			}

			return false;
		}

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
		public static function arrayToObject(array $arr): stdClass {
			$obj = new stdClass();

			foreach ($arr as $key => $val) {
				if (is_array($val)) {
					$obj->$key = self::arrayToObject($val);
				} else {
					$obj->$key = $val;
				}
			}

			return $obj;
		}
	}