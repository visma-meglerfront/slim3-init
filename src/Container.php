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

		#[\ReturnTypeWillChange]
		public function offsetGet($offset) {
			try {
				return $this->get($offset);
			} catch (Exception $e) {
				return null;
			}
		}

		public function offsetSet($offset, $value): void {
			$this->set($offset, $value);
		}

		public function offsetUnset($offset): void {
			$this->set($offset, null);
		}
	}