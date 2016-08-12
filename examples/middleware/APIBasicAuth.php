<?php
	use Adepto\Slim3Init\{
		Middleware\BasicAuth,
		Handlers\PrivilegedHandler,
		Exceptions\UnauthorizedException
	};

	class APIBasicAuth extends BasicAuth {

		protected function authorize(array $credentials): array {
			// Retrieve client
			$client = APIClient::fromPackageAndKey($credentials['username'], $credentials['password']);

			if (!$client) {
				throw new UnauthorizedException('Username/Package and Password/Key are not known to us.');
			}

			return [
				PrivilegedHandler::CONTAINER_CLIENT		=>	$client
			];
		}
	}