<?php
	namespace Adepto\Slim3Init\Exceptions;

	use Adepto\Slim3Init\Request;
	use Throwable;

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

		public function __construct(Request $request, string $message, ?Throwable $previous = null) {
			parent::__construct($message, 500, $previous);
			$this->request = $request;
		}

		/**
		 * @return Request
		 */
		public function getRequest(): Request {
			return $this->request;
		}
	}