<?php
	use Adepto\Slim3Init\Exceptions\AccessDeniedException;

	use Adepto\Slim3Init\Handlers\{
		PrivilegedHandler
	};

	use Adepto\Slim3Init\{
		Attributes\Route,
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
		#[Route('GET', '/permissions')]
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
		#[Route('GET', '/data/{id:[0-9]+}')]
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
	}