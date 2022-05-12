<?php
	namespace Adepto\Slim3Init;

	use Adepto\Slim3Init\Handlers\{
		Handler,
		Route
	};

	use Adepto\Slim3Init\Exceptions\InvalidRequestException;
	use Slim\Psr7\Factory\UriFactory;
	use Slim\Psr7\Headers;
	use FastRoute\RouteParser\Std as FastRouteParser;

	use BadMethodCallException;
	use stdClass;

	/**
	 * HandlerCaller
	 * An adapter for making calls to any {@see Handler} without using actual HTTP requests.
	 *
	 * @author  bluefirex
	 * @version 1.2
	 */
	class HandlerCaller {
		protected ?Container $container;
		protected mixed $handler;
		protected string $baseURL;
		protected FastRouteParser $routeParser;

		protected array $routesCache = [];

		public static function create(string $baseURL, string $handlerClass, ?Container $container = null): self {
			return new self($baseURL, $handlerClass, $container);
		}

		/**
		 * @deprecated Use {@link HandlerCaller::create()}
		 * Create a HandlerCaller.
		 *
		 * @param string            $baseURL      Base-URL that the handler would normally be called under (no specific request URL!)
		 * @param string            $handlerClass Class Name of the Handler to adapt to
		 * @param Container|null    $container    If supplied, this will be used as the container for the handler.
		 */
		public function __construct(string $baseURL, string $handlerClass, ?Container $container = null) {
			$this->container = $container ?? new Container();

			$this->handler = new $handlerClass($this->container);
			$this->baseURL = $baseURL;
			$this->routeParser = new FastRouteParser();
		}

		/**
		 * Get the slim container for this caller.
		 *
		 * @return Container
		 */
		public function getContainer(): Container {
			return $this->container;
		}

		/**
		 * Get the handler.
		 *
		 * @return Handler
		 */
		public function getHandler(): Handler {
			return $this->handler;
		}

		/**
		 * Get the handler's base URL.
		 *
		 * @return string
		 */
		public function getBaseURL(): string {
			return $this->baseURL;
		}

		protected function sanitizeURL($url): array|string|null {
			return preg_replace('#\?.*$#', '', $url);
		}

		/**
		 * Parse a route's URL into its arg parts
		 *
		 * @param  string $routeURL URL of the route
		 * @param  string $url      URL of match against
		 *
		 * @return array|null {@see FastRouteParser::parse}
		 */
		protected function parseRoute(string $routeURL, string $url): ?array {
			$parsedRouteURLs = $this->routeParser->parse($routeURL);
			$sanitizedURL = $this->sanitizeURL($url);

			foreach ($parsedRouteURLs as $parsedRouteURL) {
				$regex = '';

				foreach ($parsedRouteURL as $part) {
					if (is_array($part)) {
						$regex .= $part[1];
					} else {
						$regex .= $part;
					}
				}

				if (preg_match('#^' . $regex . '$#', $sanitizedURL)) {
					return $parsedRouteURL;
				}
			}

			return null;
		}

		/**
		 * Get the routes for a specific URL, i.e. /groups/3
		 *
		 * @param string $url URL
		 *
		 * @throws InvalidRequestException If route could not be found for $url
		 *
		 * @return Route[]
		 */
		protected function getRoutesForURL(string $url): array {
			if (!isset($this->routesCache[$url])) {
				/** @var Handler $class */
				$class = get_class($this->handler);
				$routes = $class::getRoutes();

				foreach ($routes as $route) {
					if ($this->parseRoute($route->getURL(), $url)) {
						if (!isset($this->routesCache[$url])) {
							$this->routesCache[$url] = [];
						}

						$this->routesCache[$url][$route->getHTTPMethod()] = $route;
					}
				}
			}

			if (empty($this->routesCache[$url])) {
				throw new InvalidRequestException('Route for ' . $url . ' not found.');
			}

			return $this->routesCache[$url];
		}

		/**
		 * Get the class method for a URL.
		 *
		 * @param string $url    URL
		 * @param string $method HTTP method
		 *
		 * @return string
		 * @throws InvalidRequestException
		 */
		protected function getClassMethodForURL(string $url, string $method = ''): string {
			$routes = $this->getRoutesForURL($url);

			if (empty($method) && !empty($routes)) {
				$route = array_shift($routes);

				return $route->getClassMethod();
			}

			if (empty($routes[$method])) {
				throw new BadMethodCallException('Could not find class method in ' . get_class($this->handler) . ' for "' . $url . '"');
			}

			return $routes[$method]->getClassMethod();
		}

		/**
		 * Throws an exception if HTTP method is not allowed for a URL.
		 *
		 * @param string $url    URL
		 * @param string $method If not empty, checks if $method is allowed and throws InvalidRequestException if not.
		 *
		 * @throws InvalidRequestException If $method wasn't empty and HTTP method does not match $method
		 *
		 * @return void
		 */
		protected function isHTTPMethodAllowedForURL(string $url, string $method = ''): void {
			$routes = $this->getRoutesForURL($url);

			if (!empty($method) && !array_key_exists($method, $routes)) {
				throw new InvalidRequestException('Method not allowed: ' . $method);
			}
		}

		/**
		 * Convert a URL to an associative args-object for use with the handler.
		 *
		 * @param string $url URL
		 *
		 * @return stdClass
		 * @throws InvalidRequestException
		 */
		protected function urlToArgs(string $url): stdClass {
			$routes = $this->getRoutesForURL($url);
			$route = array_shift($routes);
			$parsedRoute = $this->parseRoute($route->getURL(), $url);
			$regex = '';

			$argNames = [];
			$argValues = [];

			# Build arg names and regex
			foreach ($parsedRoute as $urlPart) {
				if (is_array($urlPart)) {
					$regex .= '(' . $urlPart[1] . ')';
					$argNames[] = $urlPart[0];
				} else {
					$regex .= $urlPart;
				}
			}

			# Match argument values
			preg_match('#^' . $regex . '$#', $this->sanitizeURL($url), $argValues);
			unset($argValues[0]);

			# Combine values with names
			$argsArray = array_combine($argNames, $argValues);

			# Convert to object
			return SlimInit::arrayToObject($argsArray);
		}

		/**
		 * Convert an headers array to a {@see Headers} collection.
		 *
		 * @param array $headers Headers
		 *
		 * @return Headers
		 */
		protected function headerArrayToCollection(array $headers): Headers {
			$collection = new Headers();

			foreach ($headers as $headerKey => $value) {
				if (is_array($value)) {
					foreach ($value as $v) {
						$collection->addHeader($headerKey, $v);
					}
				} else {
					$collection->setHeader($headerKey, $value);
				}
			}

			return $collection;
		}

		/**
		 * Do a request.
		 *
		 * @param string $method  HTTP Method, i.e. 'GET', 'POST', ...
		 * @param string $url     URL relative to {@see $this->getBaseURL()}
		 * @param array  $headers Headers
		 * @param mixed  $body    If array, this is converted to JSON or FORM (depending on Content-Type header). If string, it's sent raw.
		 * @param array  $files   Files to send, default = []
		 *
		 * @return string
		 * @throws InvalidRequestException
		 */
		protected function doRequest(string $method, string $url, array $headers, mixed $body, array $files = []): string {
			$this->isHTTPMethodAllowedForURL($url, $method);
			$classMethod = $this->getClassMethodForURL($url, $method);

			// if $body is an array, build a proper request body
			if (is_array($body)) {
				// If Content-Type is set to application/json, create a JSON-encoded body
				if (isset($headers['Content-Type']) && str_contains($headers['Content-Type'], 'application/json')) {
					$body = json_encode($body);
				} else {
					$body = http_build_query($body);
				}
			}

			$uri = (new UriFactory())->createUri($this->getBaseURL() . $url);
			$body = new SlimMockBody($body);
			$args = $this->urlToArgs($url);
			$request = new Request($method, $uri, $this->headerArrayToCollection($headers), [], [], $body, $files);
			$response = new Response();

			return (string) $this->handler->$classMethod($request, $response, $args)->getBody();
		}

		/**
		 * Make a GET request to $url with $headers.
		 *
		 * @param string $url     URL relative to {@see $this->getBaseURL()}
		 * @param array  $headers Headers, default = []
		 *
		 * @return string
		 * @throws InvalidRequestException
		 */
		public function get(string $url, array $headers = []): string {
			return $this->doRequest('GET', $url, $headers, '', []);
		}

		/**
		 * Make a POST request to $url with $headers and $body.
		 *
		 * @param string $url     URL relative to {@see $this->getBaseURL()}
		 * @param array  $headers Headers, default = []
		 * @param mixed  $body    If array, this is converted to JSON or FORM (depending on Content-Type header). If string, it's sent raw.
		 *
		 * @return string
		 * @throws InvalidRequestException
		 */
		public function post(string $url, array $headers, mixed $body): string {
			return $this->doRequest('POST', $url, $headers, $body, []);
		}

		/**
		 * Make a PUT request to $url with $headers, $body and $files.
		 *
		 * @param string $url     URL relative to {@see $this->getBaseURL()}
		 * @param array  $headers Headers
		 * @param mixed  $body    If array, this is converted to JSON or FORM (depending on Content-Type header). If string, it's sent raw.
		 * @param array  $files   Files to send, default = []
		 *
		 * @return string
		 * @throws InvalidRequestException
		 */
		public function put(string $url, array $headers, mixed $body, array $files = []): string {
			return $this->doRequest('PUT', $url, $headers, $body, $files);
		}

		/**
		 * Make a PATCH request to $url with $headers, $body and $files.
		 *
		 * @param string $url     URL relative to {@see $this->getBaseURL()}
		 * @param array  $headers Headers
		 * @param mixed  $body    If array, this is converted to JSON or FORM (depending on Content-Type header). If string, it's sent raw.
		 * @param array  $files   Files to send, default = []
		 *
		 * @return string
		 * @throws InvalidRequestException
		 */
		public function patch(string $url, array $headers, mixed $body, array $files = []): string {
			return $this->doRequest('PATCH', $url, $headers, $body, $files);
		}

		/**
		 * Make a DELETE request to $url with $headers and $body.
		 *
		 * @param string       $url     URL relative to {@see $this->getBaseURL()}
		 * @param array        $headers Headers
		 * @param array|string $body    If array, this is converted to JSON or FORM (depending on Content-Type header). If string, it's sent raw.
		 *
		 * @return string
		 * @throws InvalidRequestException
		 */
		public function delete(string $url, array $headers, array|string $body = ''): string {
			return $this->doRequest('DELETE', $url, $headers, $body);
		}

		/**
		 * @param mixed $handler
		 *
		 * @return HandlerCaller
		 */
		public function setHandler(mixed $handler): self {
			$this->handler = $handler;

			return $this;
		}
	}
