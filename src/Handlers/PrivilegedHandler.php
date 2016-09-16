<?php
	namespace Adepto\Slim3Init\Handlers;

	use Adepto\Slim3Init\{
		Handlers\Handler,
		Client\Client,
		Exceptions\AccessDeniedException
	};

	/**
	 * PrivilegedHandler
	 * Interface indicating that a handler has permissions to check for.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.slim-init.handlers
	 */
	abstract class PrivilegedHandler extends Handler {
		const CONTAINER_CLIENT = 'Client';

		/**
		 * Get the client currently using this handler.
		 *
		 * @return Adepto\Slim3Init\Client\Client
		 */
		public function getClient() {
			$client = $this->container->{self::CONTAINER_CLIENT};

			if (!$client instanceof Client) {
				throw new \InvalidArgumentException('Client does not implements Adepto\\Slim3Init\\Client\\Client.');
			}

			return $client;
		}

		/**
		 * Check whether a certain action is allowed.
		 * This method has to gain information on how to check it itself.
		 *
		 * @param string $action Action to check for
		 * @param array  $data   Additional data to pass around
		 *
		 * @return bool
		 */
		public function actionAllowed(string $action, array $data = []): bool {
			return $this->getClient()->hasPermission($action, $data);
		}

		/**
		 * Force a specific permission and throw an exception if action is not allowed.
		 * Make sure to override {@link actionAllowed}, if you need something special.
		 *
		 * @param string $action Action to check for
		 * @param array  $data   Additional data to pass around
		 *
		 * @throws Adepto\Slim3Init\Exceptions\AccessDeniedException if action is not allowed
		 * 
		 * @return bool
		 */
		public function forcePermission(string $action, array $data = []): bool {
			if (!$this->actionAllowed($action, $data)) {
				throw new AccessDeniedException('You\'re not allowed to perform this action.');
			}

			return true;
		}
	}