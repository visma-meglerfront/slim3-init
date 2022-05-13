<?php
	use Adepto\Slim3Init\HandlerCaller;

	use Adepto\Slim3Init\{
		Handlers\Handler,
		Attributes\Route,
		Exceptions\InvalidRequestException,
		Request,
		Response
	};

	class ExampleHandler extends Handler {

		/**
		 * getEcho
		 *
		 * @param Request  $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 */
		#[Route('GET', '/echo', arguments: ['addArg' => 'arg'], name: 'get-echo')]
		public function getEcho(Request $request, Response $response, stdClass $args): Response {
			return $response->withJson($request->getQueryParams());
		}

		/**
		 * getNamedRoute
		 *
		 * @param Request  $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 *
		 * @throws
		 */
		#[Route('GET', '/named-route', name: 'get-named-route')]
		public function getNamedRoute(Request $request, Response $response, stdClass $args): Response {
			return $response->write($this->getPathFor('get-echo'));
		}

		/**
		 * postEcho
		 *
		 * @param Request  $request  Request
		 * @param Response $response Response
		 * @param stdClass $args     Arguments
		 *
		 * @return Response
		 */
		#[Route('POST', '/echo', name: 'post-echo')]
		public function postEcho(Request $request, Response $response, stdClass $args): Response {
			return $response->withJson($request->getParsedBody());
		}

		/**
		 * getException
		 *
		 * @param Request  $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 * @throws InvalidRequestException
		 */
		#[Route('GET', '/exception')]
		public function getException(Request $request, Response $response, stdClass $args): Response {
			$first = new InvalidRequestException('A exception that happened first');
			$second = new InvalidRequestException('Halp! Invalid request for example', 20, $first);

			throw $second;
		}

		/**
		 * getException
		 *
		 * @param Request  $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 */
		#[Route('PATCH', '/method-not-allowed')]
		public function patchMethodNotAllowed(Request $request, Response $response, stdClass $args): Response {
			return $response->withJson(['message' => 'Call this method as GET or POST.']);
		}

		/**
		 * getError
		 *
		 * @param Request  $request   Request
		 * @param Response $response  Response
		 * @param stdClass $args      Arguments
		 *
		 * @return Response
		 */
		#[Route('GET', '/error')]
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
		#[Route('GET', '/caller')]
		public function getCaller(Request $request, Response $response, stdClass $args): Response {
			$caller = new HandlerCaller('/', ExampleHandler::class);

			return $response->withJson($caller->get('/echo?calledBy=ExampleHandler'));
		}
	}