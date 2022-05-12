<?php
	namespace Adepto\Slim3Init;

	use ArrayAccess;
	use Exception;

	/**
	 * A container for dependency injection.
	 * Implemented on top of {@see \DI\Container}, adds {@link \ArrayAccess} without exceptions as convenience
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class Container extends \DI\Container implements ArrayAccess {

		public function offsetExists($offset): bool {
			return $this->has($offset);
		}

		public function offsetGet($offset): mixed {
			try {
				return $this->get($offset);
			} catch (Exception) {
				return null;
			}
		}

		public function offsetSet(mixed $offset, mixed $value): void {
			$this->set($offset, $value);
		}

		public function offsetUnset(mixed $offset): void {
			$this->set($offset, null);
		}
	}