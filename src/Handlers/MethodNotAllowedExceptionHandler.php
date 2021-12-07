<?php
	namespace Adepto\Slim3Init\Handlers;

	use Adepto\Slim3Init\Request;
	use Psr\Http\Message\ResponseInterface;
	use Slim\Routing\RouteContext;
	use Throwable;

	/**
	 * Handler for handling Method Not Allowed (405)
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class MethodNotAllowedExceptionHandler extends ExceptionHandler {

		/**
		 * Get the allowed methods for a specific request
		 *
		 * @param Request $request
		 *
		 * @return string[]
		 */
		public function getAllowedMethods(Request $request): array {
			$routeContext = RouteContext::fromRequest($request);
			$routingResults = $routeContext->getRoutingResults();

			return $routingResults->getAllowedMethods();
		}

		public function handle(Request $request, Throwable $t, bool $displayDetails): ResponseInterface {
			$methods = $this->getAllowedMethods($request);
			$res = $this->createResponse(405);

			$res = $res->withJson([
				'status'         => 'error',
				'message'        => 'Method not allowed',
				'allowedMethods' => $methods
			]);

			return $res->withHeader('Allow', implode(', ', $methods));
		}
	}