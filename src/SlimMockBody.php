<?php
	namespace Adepto\Slim3Init;

	use Slim\Psr7\Stream;

	/**
	 * SlimMockBody
	 * A mock body for use with Slim.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.slim-init
	 */
	class SlimMockBody extends Stream {

		public function __construct($str) {
			$stream = fopen('php://memory', 'r+');
			fwrite($stream, $str);
			rewind($stream);

			parent::__construct($stream);
		}
	}
