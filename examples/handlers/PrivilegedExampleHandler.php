<?php
	use Adepto\Slim3Init\{
		Handlers\PrivilegedHandler,
		Handlers\Route
	};

	use Psr\Http\Message\{
		ServerRequestInterface,
		ResponseInterface
	};

	class PrivilegedExampleHandler extends PrivilegedHandler {

		/**
		 * getPermissions
		 * 
		 * @param Psr\Http\Message\ServerRequestInterface   $request   Request
		 * @param Psr\Http\Message\ResponseInterface        $response  Response
		 * @param \stdClass                                 $args      Arguments
		 *
		 * @return Psr\Http\Message\ResponseInterface
		 */
		public function getPermissions(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args): ResponseInterface {
			$perms = [];

			foreach ($this->getClient()->getPermissions() as $perm) {
				$perms[] = [
					'permission'	=>	$perm->getName(),
					'data'			=>	$perm->getData()
				];
			}

			return $response->withJSON($perms);
		}

		/**
		 * getData
		 * 
		 * @param Psr\Http\Message\ServerRequestInterface   $request   Request
		 * @param Psr\Http\Message\ResponseInterface        $response  Response
		 * @param \stdClass                                 $args      Arguments
		 *
		 * @return Psr\Http\Message\ResponseInterface
		 */
		public function getData(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args): ResponseInterface {
			$this->forcePermission('example.perm', array_diff((array) $args, [2]));

			return $response;
		}

		public function actionAllowed(string $action, array $data = []): bool {
			return $this->getClient()->hasPermission($action, $data);
		}

		public static function getRoutes(): array {
			return [
				new Route('GET', '/permissions', 'getPermissions'),
				new Route('GET', '/data/{id:[0-9]+}', 'getData')
			];
		}
	}