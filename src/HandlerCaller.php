<?php
	namespace Adepto\Slim3Init;

	use Slim\{
		Container
	};

	use Slim\Http\{
		Uri,
		Headers,
		Request,
		Response
	};

	use FastRoute\RouteParser\Std as FastRouteParser;
	use Psr\Container\ContainerInterface;

	use Adepto\Slim3Init\{
		Handlers\Route,
		Exceptions\InvalidRequestException
	};

	/**
	 * HandlerCaller
	 * An adapter for making calls to any {@see Handler} without using actual HTTP requests.
	 *
	 * @author  bluefirex
	 * @version 1.2
	 * @package as.adepto.slim-init
	 */
	class HandlerCaller {
		protected $container;
		protected $handler;
		protected $baseURL;
		protected $routeParser;

		protected $routesCache = [];


		/**
		 * Create a HandlerCaller.
		 *
		 * @param string             $baseURL      Base-URL that the handler would normally be called under (no specific request URL!)
		 * @param string             $handlerClass Class Name of the Handler to adapt to
		 * @param ContainerInterface $container    If supplied, this will be used as the container for the handler.
		 */
		public function __construct(string $baseURL, $handlerClass, ContainerInterface $container = null) {
			$this->container = $container ?? new Container([
				'settings'	=>	[
					'displayErrorDetails'	=>	true
				]
			]);

			$this->handler = new $handlerClass($this->container);
			$this->baseURL = $baseURL;
			$this->routeParser = new FastRouteParser();
		}

		/**
		 * Get the slim container for this caller.
		 *
		 * @return Slim\Container
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
			return $this->Handler;
		}

		/**
		 * Get the handler's base URL.
		 *
		 * @return string
		 */
		public function getBaseURL(): string {
			return $this->baseURL;
		}

		protected function sanitizeURL($url) {
			return preg_replace('#\?.*$#', '', $url);
		}

		/**
		 * Parse a route's URL into its arg parts
		 *
		 * @param  string $routeURL URL of the route
		 * @param  string $url      URL of match against
		 *
		 * @return array|null {@see FastRoute\RouteParser\Std::parse}
		 */
		protected function parseRoute(string $routeURL, string $url) {
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
				/**
				 * @var Route[] $routes
				 */
				$routes = get_class($this->handler)::getRoutes();

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
		 * @param string $url URL
		 * @param string $method HTTP method
		 *
		 * @return string
		 */
		protected function getClassMethodForURL(string $url, string $method = ''): string {
			$routes = $this->getRoutesForURL($url);

			if (empty($method) && !empty($routes)) {
				return $routes[0];
			}

			if (empty($routes[$method])) {
				throw new \BadMethodCallException('Could not find class method in ' . get_class($this->handler) . ' for "' . $url . '"');
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
		 * @return \stdClass
		 */
		protected function urlToArgs(string $url): \stdClass {
			$route = $this->getRouteForURL($url);
			$parsedRoute = $this->parseRoute($route->getURL(), $url);
			$args = new \stdClass();
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
		 * Convert an headers array to a {@see Slim\Http\Headers} collection.
		 *
		 * @param array $headers Headers
		 *
		 * @return Slim\Http\Headers
		 */
		protected function headerArrayToCollection(array $headers): Headers {
			$collection = new Headers();

			foreach ($headers as $headerKey => $value) {
				if (is_array($value)) {
					foreach ($value as $v) {
						$collection->add($headerKey, $v);
					}
				} else {
					$collection->set($headerKey, $value);
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
		 */
		protected function doRequest(string $method, string $url, array $headers, $body, array $files = []): string {
			$this->isHTTPMethodAllowedForURL($url, $method);
			$classMethod = $this->getClassMethodForURL($url, $method);

			// if $body is an array, build a proper request body
			if (is_array($body)) {
				// If Content-Type is set to application/json, create a JSON-encoded body
				if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') !== false) {
					$body = json_encode($body);
				} else {
					$body = http_build_query($body);
				}
			}

			$uri = Uri::createFromString($this->getBaseURL() . $url);
			$body = new SlimMockBody($body);
			$args = $this->urlToArgs($url);
			$request = new Request($method, $uri, $this->headerArrayToCollection($headers), $cookies = [], $serverParams = [], $body, $files = []);
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
		 */
		public function post(string $url, array $headers, $body): string {
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
		 */
		public function put(string $url, array $headers, $body, array $files = []): string {
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
		 */
		public function patch(string $url, array $headers, $body, array $files = []) {
			return $this->doRequest('PATCH', $url, $headers, $body, $files);
		}

		/**
		 * Make a DELETE request to $url with $headers and $body.
		 *
		 * @param string $url     URL relative to {@see $this->getBaseURL()}
		 * @param array  $headers Headers
		 * @param string $body    If array, this is converted to JSON or FORM (depending on Content-Type header). If string, it's sent raw.
		 *
		 * @return string
		 */
		public function delete(string $url, array $headers, $body = '') {
			return $this->doRequest('DELETE', $url, $headers, $body);
		}
	}
