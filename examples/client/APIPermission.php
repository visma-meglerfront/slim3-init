<?php
	class APIPermission implements Adepto\Slim3Init\Client\Permission {
		protected $name;
		protected $data;

		public function __construct($name, $data = []) {
			$this->name = $name;
			$this->data = $data;
		}

		public function getName(): string {
			return $this->name;
		}

		public function getData(): array {
			return $this->data;
		}

		public function __toString() {
			return $this->getName();
		}
	}