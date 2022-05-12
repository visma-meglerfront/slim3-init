<?php
	namespace Adepto\Slim3Init;

	use Psr\Http\Message\ResponseInterface;
	use Slim\Psr7\Response as SlimResponse;

	/**
	 * A response
	 * Implemented on top of {@link \Slim\Psr7\Response}, adds some convenience methods
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class Response extends SlimResponse {

		public static function fromSlimResponse(SlimResponse|ResponseInterface|self $response): SlimResponse|self {
			if (!$response instanceof self) {
				return $response;
			}

			return new self(
				$response->status,
				$response->headers,
				$response->body
			);
		}

		/**
		 * Encode JSON into this request
		 *
		 * @param mixed $json               Value to encode
		 * @param int   $status             HTTP status code
		 * @param int   $encodingOptions    PHP JSON_* constants to apply to encoding
		 *
		 * @return Response
		 */
		public function withJson(mixed $json, int $status = -1, int $encodingOptions = 0): Response {
			$res = $this->withHeader('Content-Type', 'application/json; charset=utf-8');

			if ($status > -1) {
				$res = $res->withStatus($status);
			}

			$res->getBody()->write(json_encode($json, $encodingOptions));

			return $res;
		}

		/**
		 * Write something into the body
		 *
		 * @param string $body Something
		 *
		 * @return $this
		 */
		public function write(string $body): Response {
			$this->getBody()->write($body);

			return $this;
		}
	}