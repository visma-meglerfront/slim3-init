<?php
	namespace Adepto\Slim3Init\Client;

	/**
	 * Client
	 * A base interface for a client that can be authorized.
	 *
	 * @author  bluefirex
	 * @version 2.0
	 */
	interface Client {

		/**
		 * Get the client's username
		 *
		 * @return string
		 */
		public function getUsername(): string;

		/**
		 * Get the eprmissions of this client
		 *
		 * @return array
		 */
		public function getPermissions(): array;

		/**
		 * Check whether this client has a specific permission
		 *
		 * @param string $name Name of the permission
		 * @param array  $data
		 *
		 * @return boolean
		 */
		public function hasPermission(string $name, array $data = []): bool;

		/**
		 * Check whether this client has any of the specified permissions.
		 *
		 * @param  array   $permissions Permission Names or {@see Permission} objects
		 *
		 * @return boolean
		 */
		public function hasAnyPermission(array $permissions): bool;

		/**
		 * Check whether this client has all of the specified permissions.
		 *
		 * @param  array   $permissions Permission Names or {@see Permission} objects
		 *
		 * @return boolean
		 */
		public function hasAllPermissions(array $permissions): bool;
	}