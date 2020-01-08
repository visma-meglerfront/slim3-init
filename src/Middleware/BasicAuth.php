<?php
	namespace Adepto\Slim3Init\Middleware;

	use Psr\Container\ContainerInterface;

	use Psr\Http\Message\{
		ServerRequestInterface,
		ResponseInterface
	};

	use Adepto\Slim3Init\{
		Client\Client,
		Exceptions\InvalidRequestException,
		Exceptions\UnauthorizedException
	};

	/**
	 * BasicAuth
	 * HTTP authorization using Basic.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.slim-init.middleware
	 */
	abstract class BasicAuth {
		const EXCEPTION_AUTH_MISSING = 1;
		const EXCEPTION_AUTH_OVERLOAD = 2;
		const EXCEPTION_AUTH_UNSUPPORTED = 3;

		protected $container;
		protected $realm;

		public function __construct(ContainerInterface $container, $realm = 'API') {
			$this->container = $container;
			$this->realm = $realm;
		}

		/**
		 * Parse Basic-credentials
		 * Those credentials are base64-encoded strings of colon-separated credentials.
		 *
		 * @param ServerRequestInterface $request Request
		 *
		 * @return array                          [ 'username' => '...', 'password' => '...' ]
		 */
		protected function parseCredentials(ServerRequestInterface $request): array {
			// See if header is present at all
			if (!$request->hasHeader('Authorization')) {
				throw new InvalidRequestException('Authorization header missing.', self::EXCEPTION_AUTH_MISSING);
			}

			// See if header is present only once
			$authHeader = $request->getHeader('Authorization');
			
			if (count($authHeader) > 1) {
				throw new InvalidRequestException('Too much authorization information.', self::EXCEPTION_AUTH_OVERLOAD);
			}

			// See if selected method is 'Basic'
			$authHeader = $authHeader[0];

			if (mb_substr($authHeader, 0, 5) !== 'Basic') {
				throw new InvalidRequestException('Unsupported authorization method.', self::EXCEPTION_AUTH_UNSUPPORTED);
			}

			// Parse header
			$authHeader = base64_decode(mb_substr($authHeader, 6));
			$authCredentials = explode(':', $authHeader);

			if (count($authCredentials) != 2) {
				throw new InvalidRequestException('Unsupported credentials.', self::EXCEPTION_AUTH_UNSUPPORTED);
			}

			return [
				'username'		=>	$authCredentials[0],
				'password'		=>	$authCredentials[1]
			];
		}

		/**
		 * Authorize credentials and return values to add to the container.
		 *
		 * @param array  $credentials Credentials: [ 'username' => '...', 'password' => '...']
		 *
		 * @return array  Values to add to the container in key/value format
		 */
		abstract protected function authorize(array $credentials): array;

		/**
		 * Add the WWW-Authenticate header to a response.
		 *
		 * @param ResponseInterface $response Response
		 */
		protected function addAuthorizationHeader(ResponseInterface $response) {
			return $response->withHeader('WWW-Authenticate', 'Basic realm="' . $this->realm . ', please authenticate yourself."');
		}

		public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) {
			try {
				// Parse
				$credentials = $this->parseCredentials($request);
				$containerValues = $this->authorize($credentials);

				// Append Values
				foreach ($containerValues as $key => $value) {
					$this->container[$key] = $value;
					$request = $request->withAttribute($key, $value);
				}
			} catch (InvalidRequestException $e) {
				switch ($e->getCode()) {
					case self::EXCEPTION_AUTH_MISSING:
						$response = $this->addAuthorizationHeader($response)->withJson([
							'status'	=>	'error',
							'message'	=>	'No authorization provided.'
						], 401);

						return $response;

					default:
						// Let the handler handle it
						throw $e;
				}
			} catch (UnauthorizedException $e) {
				$response = $this->addAuthorizationHeader($response)->withJson([
					'status'	=>	'error',
					'message'	=>	$e->getMessage()
				], 401);

				return $response;
			}

			// Next!
			return $next($request, $response);
		}
	}