<?php
	namespace Adepto\Slim3Init\Client;

	/**
	 * Client
	 * A base interface for a client that can be authorized.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.slim-init.client
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
		 * @param string  $name Name of the permission
		 *
		 * @return boolean
		 */
		public function hasPermission(string $name, array $data = []): bool;
	}