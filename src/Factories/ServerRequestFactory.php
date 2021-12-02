<?php
	namespace Adepto\Slim3Init\Factories;

	use Adepto\Slim3Init\Request;
	use Psr\Http\Message\ServerRequestInterface;
	use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;
	use Slim\Psr7\Request as SlimRequest;

	/**
	 * ServerRequestFactory
	 * Override Slim's ServerRequestFactory to convert Slim requests to SlimInit requests.
	 *
	 * @author     bluefirex
	 * @version    1.0
	 * @date       2021-12-02
	 */
	class ServerRequestFactory extends SlimServerRequestFactory {

		/** @inheritDoc */
		public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface {
			$request = parent::createServerRequest($method, $uri, $serverParams);

			return Request::fromSlimRequest($request);
		}

		/** @inheritDoc */
		public static function createFromGlobals(): SlimRequest {
			$request = parent::createFromGlobals();

			return Request::fromSlimRequest($request);
		}
	}