<?php
	namespace Adepto\Slim3Init\Factories;

	use Slim\Factory\Psr17\SlimPsr17Factory;

	/**
	 * SlimInitPsr17Factory
	 * Override SlimInit's PSR-17 factory to convert typings
	 *
	 * @author     bluefirex
	 * @version    1.0
	 */
	class SlimInitPsr17Factory extends SlimPsr17Factory {
		protected static $responseFactoryClass = 'Adepto\Slim3Init\Factories\ResponseFactory';
		protected static $serverRequestCreatorClass = 'Adepto\Slim3Init\Factories\ServerRequestFactory';
	}