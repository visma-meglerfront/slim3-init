<?php
	namespace Adepto\Slim3Init\Helpers;

	use Psr\Log\LogLevel;

	/**
	 * Helper to determine severity levels and messages of an error
	 *
	 * @author      ricardoper (https://github.com/ricardoper/slim4-skeleton/blob/de5e67a2d24f86de29dc57bce2d17a5f463cc033/app/Handlers/Helpers/Severity.php)
	 * @license     MIT
	 * @version     1.0
	 */
	class Severity {
		/**
		 * Severity Levels
		 *
		 * @var array
		 */
		protected $levels = [
			E_DEPRECATED        => LogLevel::INFO,
			E_USER_DEPRECATED   => LogLevel::INFO,
			E_NOTICE            => LogLevel::WARNING,
			E_USER_NOTICE       => LogLevel::WARNING,
			E_STRICT            => LogLevel::WARNING,
			E_WARNING           => LogLevel::WARNING,
			E_USER_WARNING      => LogLevel::WARNING,
			E_COMPILE_WARNING   => LogLevel::WARNING,
			E_CORE_WARNING      => LogLevel::WARNING,
			E_USER_ERROR        => LogLevel::CRITICAL,
			E_RECOVERABLE_ERROR => LogLevel::CRITICAL,
			E_COMPILE_ERROR     => LogLevel::CRITICAL,
			E_PARSE             => LogLevel::CRITICAL,
			E_ERROR             => LogLevel::CRITICAL,
			E_CORE_ERROR        => LogLevel::CRITICAL,
		];


		/**
		 * Get Severity
		 *
		 * @param int $level
		 *
		 * @return string
		 */
		public function getSeverity(int $level): string {
			return $this->levels[$level] ?? LogLevel::CRITICAL;
		}

		/**
		 * Get Severity Message
		 *
		 * @param string      $message
		 * @param string|null $file
		 * @param int|null    $line
		 *
		 * @return string
		 */
		public function getMessage(string $message, ?string $file = null, ?int $line = null): string {
			$message = ucfirst($message);

			if ($file !== null && $line !== null) {
				$message .= ' in file ' . $file . ' on line ' . $line;
			}

			return $message;
		}

		/**
		 * Get Severity With Message
		 *
		 * @param int         $level
		 * @param string      $message
		 * @param string|null $file
		 * @param int|null    $line
		 *
		 * @return string
		 */
		public function getSeverityMessage(int $level, string $message, ?string $file = null, ?int $line = null): string {
			return strtoupper($this->getSeverity($level)) . ': ' . $this->getMessage($message, $file, $line);
		}
	}