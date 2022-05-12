<?php
	namespace Adepto\Slim3Init\Handlers;

	use Adepto\Slim3Init\{
		Container,
		Request,
		Response,
		SlimInit
	};

	use Psr\Http\Message\ResponseInterface;
	use Psr\Log\LoggerInterface;
	use Slim\Logger;
	use Throwable;

	class ExceptionHandler {
		protected Container $container;
		protected SlimInit $app;
		protected ?LoggerInterface $logger;
		protected bool $logException;

		/**
		 * Create a handler with a container.
		 *
		 * @param Container $container  Container
		 * @param SlimInit  $app        App that called this handler
		 */
		public function __construct(Container $container, SlimInit $app, ?LoggerInterface $logger, bool $logExceptions) {
			$this->container = $container;
			$this->app = $app;
			$this->logger = $logger ?? $this->getDefaultLogger();
			$this->logException = $logExceptions;
		}

		/**
		 * Set whether or not to log exceptions
		 *
		 * @param bool $log
		 *
		 * @return $this
		 */
		public function setLogException(bool $log): self {
			$this->logException = $log;

			return $this;
		}

		protected function getDefaultLogger(): LoggerInterface {
			return new Logger();
		}

		/**
		 * Create a response
		 *
		 * @param int    $status HTTP status code, defaults to 500
		 * @param string $message
		 *
		 * @return Response
		 */
		protected function createResponse(int $status = 500, string $message = ''): Response {
			return (new Response())->withStatus($status, $message);
		}

		/**
		 * Handle an exception
		 *
		 * @param Request   $request            Request
		 * @param Throwable $t                  Exception
		 * @param bool      $displayDetails     Whether to display details, determined by the debug header
		 *
		 * @return ResponseInterface
		 */
		public function handle(Request $request, Throwable $t, bool $displayDetails): ResponseInterface {
			$statusCode = $this->app->getStatusCodeForException($t);
			$response = $this->createResponse($statusCode);

			$content = [
				'status'		=>	'error',
				'message'		=>	$t->getMessage()
			];

			if ($t->getCode()) {
				$content['code'] = $t->getCode();
			}

			if ($statusCode == 500) {
				$content['message'] = 'An internal error happend. >.<';

				if ($this->logException) {
					$this->logger->error($t->getMessage(), [
						'stacktrace'    => $t->getTrace()
					]);
				}
			}

			/**
			 * Show more details about the exception, if enabled
			 */
			if ($statusCode == 500 && $displayDetails) {
				$content['details'] = $this->formatThrowableAsArray($t);

				if ($previous = $t->getPrevious()) {
					$content['previous'] = $this->formatThrowableAsArray($previous);
				}
			}

			return $response->withJson($content);
		}

		protected function formatThrowableAsArray(Throwable $t): array {
			return [
				'exception'		=>	get_class($t),
				'message'		=>	$t->getMessage(),
				'stacktrace'	=>	explode("\n", $t->getTraceAsString())
			];
		}
	}