<?php
	namespace Adepto\Slim3Init;

	use Slim\Psr7\Stream;

	/**
	 * SlimMockBody
	 * A mock body for use with Slim.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 */
	class SlimMockBody extends Stream {

		public function __construct(string $str) {
			$stream = fopen('php://memory', 'r+');
			fwrite($stream, $str);
			rewind($stream);

			parent::__construct($stream);
		}
	}
