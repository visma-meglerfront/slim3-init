<?php
	namespace Adepto\Slim3Init\Client;

	/**
	 * Permission
	 * Base interface for a permission to something.
	 *
	 * @author  bluefirex
	 * @version 2.0
	 */
	interface Permission {

		/**
		 * Get the name of the permission.
		 * Can be something technical, like adepto.internal.stuff
		 *
		 * @return string
		 */
		public function getName(): string;

		/**
		 * Get additional data for this permission.
		 * Can be used to make sure only sspecific stuff can be done.
		 *
		 * @return array
		 */
		public function getData(): array;
	}