<?php
	namespace Adepto\Slim3Init\Attributes;

	use Adepto\Slim3Init\Handlers\Handler;
	use Attribute;

	/**
	 * Attribute to configure a Route to a method in a {@link Handler}.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 */
	#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
	class Route {

		public function __construct(
			protected string $method,
			protected string $url,
			protected array $arguments = [],
			protected string $name = ''
		) {
		}

		/**
		 * Get the HTTP method for this route
		 *
		 * @return string
		 */
		public function getMethod(): string {
			return $this->method;
		}

		/**
		 * Get the URL for this route
		 *
		 * @return string
		 */
		public function getURL(): string {
			return $this->url;
		}

		/**
		 * Get additional arguments to pass for this route
		 *
		 * @return array
		 */
		public function getArguments(): array {
			return $this->arguments;
		}

		/**
		 * Get the name of this route
		 *
		 * @return string
		 */
		public function getName(): string {
			return $this->name;
		}


		/**
		 * Set the name of this route. Can be used to retrieve a route in a handler.
		 *
		 * @param string $name
		 *
		 * @return $this
		 */
		public function setName(string $name): static {
			$this->name = $name;

			return $this;
		}
	}