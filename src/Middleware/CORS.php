<?php
	namespace Adepto\Slim3Init\Middleware;

	use Adepto\Slim3Init\{
		Container,
		Request
	};

	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	use Slim\Routing\{
		Route,
		RouteCollector,
		RouteContext
	};

	/**
	 * CORS
	 * Middleware for handling preflight requests for supporting CORS
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class CORS {
		const DEFAULT_MAX_AGE = 60 * 60 * 24;

		protected Container $container;
		protected array $allowedOrigins;
		protected ?array $allowedHeaders;
		protected int $maxAge;

		/**
		 * Create a CORS middleware
		 *
		 * @param Container  $container
		 * @param array      $allowedOrigins Allowed origins, supply ['*'] to allow all
		 * @param array|null $allowedHeaders Allowed headers, leave null to use default headers
		 * @param int        $maxAge         Max age in seconds CORS headers are being cached for
		 */
		public function __construct(Container $container, array $allowedOrigins, ?array $allowedHeaders = null, int $maxAge = self::DEFAULT_MAX_AGE) {
			$this->container = $container;
			$this->allowedOrigins = $allowedOrigins;
			$this->allowedHeaders = $allowedHeaders ?? ['Authorization', 'Content-Type', 'DNT', 'X-Requested-With'];
			$this->maxAge = $maxAge;
		}

		/**
		 * Get all allowed origins
		 *
		 * @return string[]
		 */
		public function getAllowedOrigins(): array {
			return $this->allowedOrigins;
		}

		/**
		 * Add an allowed origin
		 *
		 * @param string $origin
		 *
		 * @return $this
		 */
		public function addAllowedOrigin(string $origin): self {
			$this->allowedOrigins[] = $origin;

			return $this;
		}

		/**
		 * Remove an allowed origin
		 *
		 * @param string $origin
		 *
		 * @return $this
		 */
		public function removeAllowedOrigin(string $origin): self {
			$index = array_search($origin, $this->allowedOrigins);

			if ($index !== false) {
				unset($this->allowedOrigins[$index]);
				$this->allowedOrigins = array_values($this->allowedOrigins);
			}

			return $this;
		}

		/**
		 * Set all allowed origins
		 *
		 * @param array $origins
		 *
		 * @return $this
		 */
		public function setAllowedOrigins(array $origins): self {
			$this->allowedOrigins = $origins;

			return $this;
		}

		/**
		 * Check whether $origin is allowed
		 *
		 * @param string $origin
		 *
		 * @return bool
		 */
		public function isOriginAllowed(string $origin): bool {
			return in_array($origin, $this->allowedOrigins);
		}

		/**
		 * Get all allowed headers
		 *
		 * @return string[]
		 */
		public function getAllowedHeaders(): array {
			return $this->allowedHeaders;
		}

		/**
		 * Add an allowed header
		 *
		 * @param string $header
		 *
		 * @return $this
		 */
		public function addAllowedHeader(string $header): self {
			$this->allowedHeaders[] = $header;

			return $this;
		}

		/**
		 * Remove an allowed header
		 *
		 * @param string $header
		 *
		 * @return $this
		 */
		public function removeAllowedHeader(string $header): self {
			$index = array_search($header, $this->allowedHeaders);

			if ($index !== false) {
				unset($this->allowedHeaders[$index]);
				$this->allowedHeaders = array_values($this->allowedHeaders);
			}

			return $this;
		}

		/**
		 * Set all allowed headers
		 *
		 * @param array $headers
		 *
		 * @return $this
		 */
		public function setAllowedHeaders(array $headers): self {
			$this->allowedHeaders = $headers;
			return $this;
		}

		/**
		 * Check whether $header is allowed
		 *
		 * @param string $header
		 *
		 * @return bool
		 */
		public function isHeaderAllowed(string $header): bool {
			return in_array($header, $this->allowedHeaders);
		}

		/**
		 * Max age for caching the CORS headers
		 *
		 * @param int $maxAge Age in seconds
		 *
		 * @return CORS
		 */
		public function setMaxAge(int $maxAge): self {
			$this->maxAge = $maxAge;

			return $this;
		}

		/**
		 * @return int  Age in seconds
		 */
		public function getMaxAge(): int {
			return $this->maxAge;
		}

		protected function addCORSHeaders(Request $request, ResponseInterface $response): ResponseInterface {
			$serverParams = $request->getServerParams();

			if ($this->isOriginAllowed('*')) {
				$response = $response->withHeader('Access-Control-Allow-Origin', '*');
			} else if (isset($serverParams['HTTP_ORIGIN']) && $this->isOriginAllowed($serverParams['HTTP_ORIGIN'])) {
				// If origin is known, return that as allowed origin
				$response = $response->withHeader('Access-Control-Allow-Origin', $serverParams['HTTP_ORIGIN']);
			}

			return $response->withHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders))
			                ->withHeader('Access-Control-Allow-Credentials', 'true')
							->withHeader('Access-Control-Max-Age', $this->maxAge);
		}

		public function __invoke(Request $request, RequestHandlerInterface $handler): ResponseInterface {
			$response = $handler->handle($request);

			if ($request->getMethod() == 'OPTIONS') {
				/** @var RouteCollector $router */
				$routeCollector = $this->container['route_collector'];
				$routeContext = RouteContext::fromRequest($request);
				$currentRoute = $routeContext->getRoute();

				if ($routeCollector && $currentRoute) {
					$methods = [];

					/** @var Route $route */
					foreach ($routeCollector->getRoutes() as $route) {
						if ($route->getPattern() == $currentRoute->getPattern() && $route->getMethods() != ['OPTIONS']) {
							$methods = array_merge($methods, $route->getMethods());
						}
					}

					if (count($methods)) {
						$response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', array_unique($methods)));
					}
				}
			}

			return $this->addCORSHeaders($request, $response);
		}
	}