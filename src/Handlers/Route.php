<?php
	namespace Adepto\Slim3Init\Handlers;

	use Adepto\Slim3Init\Attributes\Route as RouteAttribute;
	use Attribute;

	/**
	 * Route
	 *
	 * @author  bluefirex
	 * @version 1.3
	 */
	#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
	class Route {
		protected string $httpMethod;
		protected string $url;
		protected string $classMethod;
		protected array $arguments;
		protected string $name;

		/**
		 * Create a Route
		 *
		 * @param string $httpMethod
		 * @param string $url
		 * @param string $classMethod
		 * @param array  $arguments
		 * @param string $name
		 */
		public function __construct(string $httpMethod, string $url, string $classMethod, array $arguments = [], string $name = '') {
			$this->httpMethod = $httpMethod;
			$this->url = $url;
			$this->classMethod = $classMethod;
			$this->arguments = $arguments;
			$this->name = $name;
		}

		/**
		 * The HTTP method, i.e. GET, POST, PATCH, ...
		 *
		 * @return string
		 */
		public function getHTTPMethod(): string {
			return $this->httpMethod;
		}

		/**
		 * The Slim-compatible regex-like pattern, i.e. '/data/{id:[0-9+]}'
		 *
		 * @return string
		 */
		public function getURL(): string {
			return $this->url;
		}

		/**
		 * The handler's class method to be called
		 *
		 * @return string
		 */
		public function getClassMethod(): string {
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

		/**
		 * Get the name of the route
		 *
		 * @return string
		 */
		public function getName(): string {
			return $this->name;
		}

		public static function fromAttribute(RouteAttribute $attribute, string $classMethod): self {
			return new self(
				$attribute->getMethod(),
				$attribute->getURL(),
				$classMethod,
				$attribute->getArguments(),
				$attribute->getName()
			);
		}
	}