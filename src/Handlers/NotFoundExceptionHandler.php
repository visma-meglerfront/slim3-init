<?php
	namespace Adepto\Slim3Init\Handlers;

	use Adepto\Slim3Init\Request;
	use Psr\Http\Message\ResponseInterface;
	use Throwable;

	/**
	 * Handler for handling 404-type responses
	 *
	 * @author     bluefirex
	 * @version    1.0
	 * @date       2021-12-07
	 */
	class NotFoundExceptionHandler extends ExceptionHandler {

		public function handle(Request $request, Throwable $t, bool $displayDetails): ResponseInterface {
			return $this->createResponse(404)->withJson([
				'status'        => 'error',
				'message'       => 'Page not found.'
			]);
		}
	}