<?php
	namespace Adepto\Slim3Init\Middleware;

	use Adepto\Slim3Init\{
		Container,
		Request,
		Response
	};

	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	/**
	 * Middleware
	 * A base class for conveniently implementing middleware
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	abstract class Middleware {
		protected Container $container;

		public function __construct(Container $container) {
			$this->container = $container;
		}

		/**
		 * Create a response
		 *
		 * @param int    $status HTTP status code, defaults to 500
		 * @param string $message
		 *
		 * @return Response
		 */
		protected function createResponse(int $status = 500, string $message = ''): Response {
			return (new Response())->withStatus($status, $message);
		}

		protected function asResponse(ResponseInterface $response): Response {
			return Response::fromSlimResponse($response);
		}

		/**
		 * Run the middleware!
		 *
		 * @param Request                 $request
		 * @param RequestHandlerInterface $handler
		 *
		 * @return Response
		 */
		public abstract function __invoke(Request $request, RequestHandlerInterface $handler): Response;
	}