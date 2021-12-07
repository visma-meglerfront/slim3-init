<?php

	use Adepto\Slim3Init\Exceptions\AccessDeniedException;

	use Adepto\Slim3Init\Handlers\{
		PrivilegedHandler,
		Route
	};

	use Adepto\Slim3Init\{
		Request,
		Response
	};
	
	class PrivilegedExampleHandler extends PrivilegedHandler {

		/**
		 * getPermissions
		 *
		 * @param Request  $request  Request
		 * @param Response $response Response
		 * @param stdClass $args     Arguments
		 *
		 * @return Response
		 */
		public function getPermissions(Request $request, Response $response, stdClass $args): Response {
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
		 * @param Request  $request  Request
		 * @param Response $response Response
		 * @param stdClass $args     Arguments
		 *
		 * @return Response
		 * @throws AccessDeniedException
		 */
		public function getData(Request $request, Response $response, \stdClass $args): Response {
			$this->forcePermission('example.perm', array_diff((array) $args, [2]));

			return $response;
		}

		public function actionAllowed(string $action, array $data = []): bool {
			return $this->getClient()->hasPermission($action, $data);
		}

		/**
		 * @throws AccessDeniedException
		 */
		public function onRequest(Request $request, Response $response, stdClass $args, callable $next): Response {
			if (mt_rand(1, 3) == 2) {
				throw new AccessDeniedException('Nope');
			}

			return $next($request, $response, $args);
		}

		public static function getRoutes(): array {
			return [
				new Route('GET', '/permissions', 'getPermissions'),
				new Route('GET', '/data/{id:[0-9]+}', 'getData')
			];
		}
	}