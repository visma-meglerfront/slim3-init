<?php
	namespace Adepto\Slim3Init\Exceptions;

	use Adepto\Slim3Init\Request;

	/**
	 * InternalErrorException
	 * Indicates that an internal error occurred.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.slim-init.exceptions
	 */
	class InternalErrorException extends \Exception {
		protected $request;

		public function __construct(Request $request, string $message) {
			parent::__construct($message, 500);
			$this->request = $request;
		}

		/**
		 * @return Request
		 */
		public function getRequest(): Request {
			return $this->request;
		}
	}