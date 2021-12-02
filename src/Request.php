<?php
	namespace Adepto\Slim3Init;

	use Adepto\Slim3Init\Exceptions\InvalidRequestException;
	use DateTime;
	use DateTimeInterface;
	use Slim\Psr7\Request as SlimRequest;

	/**
	 * A response
	 * Implemented on top of {@link \Slim\Psr7\Request}, adds some convenience methods
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class Request extends SlimRequest {

		/**
		 * @param $slimRequest
		 *
		 * @return mixed
		 * @noinspection PhpReturnDocTypeMismatchInspection
		 */
		public static function fromSlimRequest($slimRequest) {
			if (!$slimRequest instanceof SlimRequest || $slimRequest instanceof self) {
				return $slimRequest;
			}

			$request = new self(
				$slimRequest->getMethod(),
				$slimRequest->uri,
				$slimRequest->headers,
				$slimRequest->cookies,
				$slimRequest->serverParams,
				$slimRequest->body,
				$slimRequest->uploadedFiles
			);

			$request->attributes = $slimRequest->attributes;

			return $request;
		}

		/**
		 * Get a single query param
		 *
		 * @param string $param     Query Param to get
		 * @param mixed  $default   Default value to return, defaults to null
		 *
		 * @return mixed
		 */
		public function getQueryParam(string $param, $default = null) {
			return $this->getQueryParams()[$param] ?? $default;
		}

		/**
		 * Get a single query param as a date
		 * Supports passing either a string recognized by {@link \DateTime}, such as "2021-12-02 14:25:00" or "yesterday, 15:00" or
		 * a timestamp (UNIX seconds).
		 *
		 * @param string                 $param     Query Param to get
		 * @param DateTimeInterface|null $default   Default value if param is not set
		 *
		 * @throws InvalidRequestException
		 */
		public function getDateQueryParam(string $param, ?DateTimeInterface $default = null): ?DateTimeInterface {
			if ($param = $this->getQueryParam($param)) {
				if (is_numeric($param)) {
					$date = new DateTime();
					$date->setTimestamp($param);
				} else {
					try {
						$date = new DateTime($param);
					} catch (\Exception $e) {
						throw new InvalidRequestException('Invalid date for \'' . $param . '\'', 400);
					}
				}

				return $date;
			}

			return $default;
		}
	}