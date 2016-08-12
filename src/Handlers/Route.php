<?php
	namespace Adepto\Slim3Init\Handlers;

	/**
	 * Route
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.slim-init.handlers
	 */
	class Route {
		protected $httpMethod;
		protected $url;
		protected $classMethod;

		public function __construct($httpMethod, $url, $classMethod) {
			$this->httpMethod = $httpMethod;
			$this->url = $url;
			$this->classMethod = $classMethod;
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
	}