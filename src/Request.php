<?php
	namespace Adepto\Slim3Init;

	use Adepto\Slim3Init\Exceptions\InvalidRequestException;
	use DateTime;
	use DateTimeInterface;
	use Exception;
	use Psr\Http\Message\ServerRequestInterface;
	use Slim\Psr7\Request as SlimRequest;

	/**
	 * A response
	 * Implemented on top of {@link \Slim\Psr7\Request}, adds some convenience methods
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class Request extends SlimRequest {

		public static function fromSlimRequest(SlimRequest|ServerRequestInterface|self $slimRequest): SlimRequest|self {
			if ($slimRequest instanceof self) {
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
		 * Get request content type.
		 *
		 * Note: This method is not part of the PSR-7 standard.
		 *
		 * @return ?string
		 */
		public function getContentType(): ?string {
			$result = $this->getHeader('Content-Type');

			return $result ? $result[0] : null;
		}

		/**
		 * Get the body's content length
		 *
		 * @return int
		 */
		public function getContentLength(): int {
			return $this->getBody()->getSize();
		}

		/**
		 * Get request media type, if known.
		 *
		 * Note: This method is not part of the PSR-7 standard.
		 *
		 * @return ?string
		 */
		public function getMediaType(): ?string {
			$contentType = $this->getContentType();

			if (is_string($contentType) && trim($contentType) !== '') {
				$contentTypeParts = explode(';', $contentType);
				return strtolower(trim($contentTypeParts[0]));
			}

			return null;
		}

		/**
		 * Get a single query param
		 *
		 * @param string     $param   Query Param to get
		 * @param mixed|null $default Default value to return, defaults to null
		 *
		 * @return mixed
		 */
		public function getQueryParam(string $param, mixed $default = null): mixed {
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
					} catch (Exception) {
						throw new InvalidRequestException('Invalid date for \'' . $param . '\'', 400);
					}
				}

				return $date;
			}

			return $default;
		}

		/**
		 * Is this an HTTP GET request?
		 *
		 * @return bool
		 */
		public function isGet(): bool {
			return $this->getMethod() == 'GET';
		}

		/**
		 * Is this an HTTP POST request?
		 *
		 * @return bool
		 */
		public function isPost(): bool {
			return $this->getMethod() == 'POST';
		}

		/**
		 * Is this an HTTP PUT request?
		 *
		 * @return bool
		 */
		public function isPut(): bool {
			return $this->getMethod() == 'PUT';
		}

		/**
		 * Is this an HTTP PATCH request?
		 *
		 * @return bool
		 */
		public function isPatch(): bool {
			return $this->getMethod() == 'PATCH';
		}

		/**
		 * Is this an HTTP HEAD request?
		 *
		 * @return bool
		 */
		public function isHead(): bool {
			return $this->getMethod() == 'HEAD';
		}

		/**
		 * Is this an HTTP OPTIONS request?
		 *
		 * @return bool
		 */
		public function isOptions(): bool {
			return $this->getMethod() == 'OPTIONS';
		}
	}