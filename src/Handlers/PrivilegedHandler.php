<?php
	namespace Adepto\Slim3Init\Handlers;

	use Adepto\Slim3Init\{
		Client\Client,
		Exceptions\AccessDeniedException
	};

	use InvalidArgumentException;

	/**
	 * PrivilegedHandler
	 * Interface indicating that a handler has permissions to check for.
	 *
	 * @author  bluefirex
	 * @version 1.1
	 * @package as.adepto.slim-init.handlers
	 */
	abstract class PrivilegedHandler extends Handler {
		const CONTAINER_CLIENT = 'Client';

		/**
		 * Get the client currently using this handler.
		 *
		 * @return Client
		 */
		public function getClient(): Client {
			$client = $this->container[self::CONTAINER_CLIENT];

			if (!$client instanceof Client) {
				throw new InvalidArgumentException('Client does not implements Adepto\\Slim3Init\\Client\\Client.');
			}

			return $client;
		}

		/**
		 * Check if this handler has been given a client through some middleware
		 *
		 * @return boolean
		 */
		public function hasClient(): bool {
			return isset($this->container->{self::CONTAINER_CLIENT});
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
		 * @throws AccessDeniedException if action is not allowed
		 *
		 * @return bool
		 */
		public function forcePermission(string $action, array $data = []): bool {
			if (!$this->actionAllowed($action, $data)) {
				throw new AccessDeniedException('You\'re not allowed to perform this action.');
			}

			return true;
		}

		/**
		 * Force any of the permissions given in $permissions.
		 * This is a direct call to the client's hasAnyPermission()-method.
		 *
		 * @param  array  $permissions Permissions
		 *
		 * @throws AccessDeniedException if none of the permissions match
		 *
		 * @return bool
		 */
		public function forceAnyPermission(array $permissions): bool {
			if (!$this->getClient()->hasAnyPermission($permissions)) {
				throw new AccessDeniedException('You\'re not allowed to perform this action.');
			}

			return true;
		}

		/**
		 * Force all permissions given in $permissions.
		 * This is a direct call to the client's hasAllPermissions()-method.
		 *
		 * @param  array  $permissions Permissions
		 *
		 * @throws AccessDeniedException if at least one of the permissions doesn't match
		 *
		 * @return bool
		 */
		public function forceAllPermissions(array $permissions): bool {
			if (!$this->getClient()->hasAllPermissions($permissions)) {
				throw new AccessDeniedException('You\'re not allowed to perform this action.');
			}

			return true;
		}
	}