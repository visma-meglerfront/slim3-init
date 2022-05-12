<?php
	namespace Adepto\Slim3Init\Middleware;


	use Adepto\Slim3Init\{
		Container,
		Exceptions\InvalidRequestException,
		Exceptions\UnauthorizedException,
		Request,
		Response
	};

	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	/**
	 * BasicAuth
	 * HTTP authorization using Basic.
	 *
	 * @author  bluefirex
	 * @version 3.0
	 */
	abstract class BasicAuth {
		const EXCEPTION_AUTH_MISSING = 1;
		const EXCEPTION_AUTH_OVERLOAD = 2;
		const EXCEPTION_AUTH_UNSUPPORTED = 3;

		protected Container $container;
		protected string $realm;

		public function __construct(Container $container, string $realm = 'API') {
			$this->container = $container;
			$this->realm = $realm;
		}

		/**
		 * Parse Basic-credentials
		 * Those credentials are base64-encoded strings of colon-separated credentials.
		 *
		 * @param Request $request Request
		 *
		 * @return array                          [ 'username' => '...', 'password' => '...' ]
		 * @throws InvalidRequestException
		 */
		protected function parseCredentials(Request $request): array {
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
		 *
		 * @throws UnauthorizedException
		 */
		abstract protected function authorize(array $credentials): array;

		/**
		 * Add the WWW-Authenticate header to a response.
		 *
		 * @param Response $response Response
		 */
		protected function addAuthorizationHeader(Response $response): Response {
			return $response->withHeader('WWW-Authenticate', 'Basic realm="' . $this->realm . ', please authenticate yourself."');
		}

		/**
		 * @throws InvalidRequestException
		 */
		public function __invoke(Request $request, RequestHandlerInterface $handler): ResponseInterface {
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
						$response = new Response();

						return $this->addAuthorizationHeader($response)->withJson([
							'status'	=>	'error',
							'message'	=>	'No authorization provided.'
						], 401);

					default:
						// Let the handler handle it
						throw $e;
				}
			} catch (UnauthorizedException $e) {
				$response = new Response();

				return $this->addAuthorizationHeader($response)->withJson([
					'status'	=>	'error',
					'message'	=>	$e->getMessage()
				], 401);
			}

			// Next!
			return $handler->handle($request);
		}
	}