<?php
	use Adepto\Slim3Init\{
		Exceptions\UnauthorizedException,
		Client\Client,
		Client\Permission
	};

	class APIClient implements Client {
		protected $package;
		protected $key;

		public function __construct($package, $key) {
			$this->package = $package;
			$this->key = $key;
		}

		public function getPackage(): string {
			return $this->package;
		}

		public function getUsername(): string {
			return $this->getPackage();
		}

		public function getKey(): string {
			return $this->key;
		}

		public function getPermissions(): array {
			return [
				new APIPermission('example.perm'),
				new APIPermission('example.perm.withData', [
					'someValue' 	=>	'value',
					'someBoolean'	=>	true,
					'someArray'		=>	[ 1, 2, 5 ]
				])
			];
		}

		/**
		 * Returns true if $data is empty, just for testing this stuff.
		 *
		 * @param  string  $name Name
		 * @param  array   $data Data
		 *
		 * @return boolean
		 */
		public function hasPermission(string $name, array $data = []): bool {
			return count($data) == 0;
		}

		/**
		 * Example credentials: test : 123456
		 *
		 * @param  string $package Package
		 * @param  string $key     Key
		 *
		 * @return APIClient
		 */
		public static function fromPackageAndKey(string $package, string $key) {
			if ($package != 'test' || $key != '123456') {
				return null;
			}

			return new self($package, $key);
		}
	}