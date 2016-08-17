<?php
	namespace Adepto\Slim3Init\Handlers;

	/**
	 * Route
	 *
	 * @author  bluefirex
	 * @version 1.1
	 * @package as.adepto.slim-init.handlers
	 */
	class Route {
		protected $httpMethod;
		protected $url;
		protected $classMethod;
		protected $arguments;

		public function __construct($httpMethod, $url, $classMethod, array $arguments = []) {
			$this->httpMethod = $httpMethod;
			$this->url = $url;
			$this->classMethod = $classMethod;
			$this->arguments = $arguments;
		}

		/**
		 * The HTTP method, i.e. GET, POST, PATCH, ...
		 *
		 * @return string
		 */
		public function getHTTPMethod() {
			return $this->httpMethod;
		}

		/**
		 * The Slim-compatible regex-like pattern, i.e. '/data/{id:[0-9+]}'
		 *
		 * @return string
		 */
		public function getURL() {
			return $this->url;
		}

		/**
		 * The handler's class method to be called
		 *
		 * @return string
		 */
		public function getClassMethod() {
			return $this->classMethod;
		}

		/**
		 * Get additional arguments to pass around to SlimInit
		 *
		 * @return array
		 */
		public function getArguments(): array {
			return $this->arguments;
		}
	}