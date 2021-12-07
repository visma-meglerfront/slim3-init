<?php
	use Adepto\Slim3Init\HandlerCaller;

	use Adepto\Slim3Init\{
		Handlers\Handler,
		Handlers\Route,
		Exceptions\InvalidRequestException,
		Request,
		Response
	};

	class ExampleHandler extends Handler {

		/**
		 * getEcho
		 *
		 * @param          $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 */
		public function getEcho(Request $request, Response $response, stdClass $args): Response {
			return $response->withJson($request->getQueryParams());
		}

		/**
		 * getNamedRoute
		 *
		 * @param          $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 *
		 * @throws
		 */
		public function getNamedRoute(Request $request, Response $response, stdClass $args): Response {
			return $response->write($this->getPathFor('get-echo'));
		}

		/**
		 * postEcho
		 *
		 * @param          $request  Request
		 * @param Response $response Response
		 * @param stdClass $args     Arguments
		 *
		 * @return Response
		 */
		public function postEcho(Request $request, Response $response, stdClass $args): Response {
			return $response->withJson($request->getParsedBody());
		}

		/**
		 * getException
		 *
		 * @param          $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 * @throws InvalidRequestException
		 */
		public function getException(Request $request, Response $response, stdClass $args): Response {
			$first = new InvalidRequestException('A exception that happened first');
			$second = new InvalidRequestException('Halp! Invalid request for example', 20, $first);

			throw $second;
		}

		/**
		 * getException
		 *
		 * @param          $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 */
		public function patchMethodNotAllowed(Request $request, Response $response, stdClass $args): Response {
			return $response->withJson(['message' => 'Call this method as GET or POST.']);
		}

		/**
		 * getError
		 *
		 * @param          $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 */
		public function getError(Request $request, Response $response, stdClass $args): Response {
			throw new Error('Halp!', 20);
		}

		/**
		 * getCaller
		 *
		 * @param Request  $request  Request
		 * @param Response $response Response
		 * @param stdClass $args     Arguments
		 *
		 * @return Response
		 * @throws InvalidRequestException
		 */
		public function getCaller(Request $request, Response $response, stdClass $args): Response {
			$caller = new HandlerCaller('/', ExampleHandler::class);

			return $response->withJson($caller->get('/echo?calledBy=ExampleHandler'));
		}

		public static function getRoutes(): array {
			return [
				new Route('GET', '/echo', 'getEcho', ['addArg' => 'arg'], 'get-echo'),
				new Route('GET', '/named-route', 'getNamedRoute', [], 'get-named-route'),
				new Route('POST', '/echo', 'postEcho', [], 'post-echo'),
				new Route('GET', '/exception', 'getException'),
				new Route('PATCH', '/method-not-allowed', 'patchMethodNotAllowed'),
				new Route('GET', '/error', 'getError'),
				new Route('GET', '/caller', 'getCaller')
			];
		}
	}