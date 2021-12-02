<?php
	require __DIR__ . '/../vendor/autoload.php';

	require __DIR__ . '/middleware/APIBasicAuth.php';

	require __DIR__ . '/client/APIClient.php';
	require __DIR__ . '/client/APIPermission.php';

	use Adepto\Slim3Init\{
		SlimInit
	};

	// Create SlimInit
	$slim = new SlimInit();

	// Add handlers, middleware and set a debug header
	$slim->addHandlersFromDirectory(__DIR__ . '/handlers')
		 ->setBasePath('/examples')
	     ->addMiddleware(new APIBasicAuth($slim->getContainer(), 'API Example'))
	     ->setDebugHeader('Debug', '1')
	     ->run();