<?php
	namespace Adepto\Slim3Init\Factories;

	use Adepto\Slim3Init\Response;
	use Fig\Http\Message\StatusCodeInterface;

	use Psr\Http\Message\{
		ResponseFactoryInterface,
		ResponseInterface
	};

	/**
	 * A factory to create {@link Response} objects
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class ResponseFactory implements ResponseFactoryInterface {
		/**
		 * {@inheritdoc}
		 */
		public function createResponse(int $code = StatusCodeInterface::STATUS_OK, string $reasonPhrase = ''): ResponseInterface {
			$res = new Response($code);

			if ($reasonPhrase !== '') {
				$res = $res->withStatus($code, $reasonPhrase);
			}

			return $res;
		}
	}