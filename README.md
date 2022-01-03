# Slim3 Init

This is a very convenient wrapper around the Slim4 framework providing us with some shortcuts and prepopulated concepts.

## Installation

Add this to `composer.json`:

```json
"require": {
	"adeptoas/slim3-init": "^4.0.0"
}
```

Make sure to merge your `require`-blocks!

## Usage

### SlimInit

- #### Create a SlimInit instance

	```php
	$app = new SlimInit();
	```

- #### Set debug header
	If this header is present in the request, it will enable additional information to be shown when using the default exception handler.
	Set null to disable this feature.

	```php
	setDebugHeader(?string $header, string $expectedValue = ''): SlimInit
	```

- #### Map an exception to an HTTP status code
	Set the HTTP status code for an exception when using the default exception handler.

	```php
	setException(string $exception, int $statusCode): SlimInit
	```

- #### Map an exception to an exception handler
	Customize the handling of an exception entirely. `$exceptionHandlerClass` must be the fully qualified name of a class
	that extends `Adepto\Slim3Init\Handlers\ExceptionHandler`.

	```php
	setException(string $exception, string $exceptionHandlerClass): SlimInit
	```

- #### Map multiple exceptions to an exception handler or status code

	```php
	setException(array $exceptions, int|string $statusCodeOrHandlerClass): SlimInit
	```

- #### Example: Add error collection services like Raygun, Sentry, …

	To send an exception to an error collection service, add an exception callback:

	```php
	/** @var $app \Adepto\Slim3Init\SlimInit */
	$app->addExceptionCallback(function(\Adepto\Slim3Init\Request $request, Throwable $t) {
		// Send $t to the service
	});
	```

	Or as a class:

	```php
	class ExceptionCallback {
		public function __invoke(\Adepto\Slim3Init\Request $request, Throwable $t) {
			// Send $t to the service
		}
	}

	/** @var $app \Adepto\Slim3Init\SlimInit */
	$app->addExceptionCallback(new ExceptionCallback());
 	```

- #### Add something to the container

	```php
	addToContainer(string $key, mixed $value): SlimInit
	```

- #### Add a single handler
	`$className` must be the name of a class that extends `Adepto\Slim3Init\Handlers\Handler`.

	```php
	addHandler(string $className): SlimInit
	```

- #### Add multiple handlers from a directory
	Add all handlers from a specific directory. Non-recursive and the filenames must be the class names followed by `.php`.

	```php
	addHandlersFromDirectory(string $dir): SlimInit
	```

- #### Add Slim 4-compatible middleware
	Refer to Slim's documentation for more information about middleware.

	```php
	addMiddleware(callable $middleware): SlimInit
	```

- #### Run!
	Boot up the application and listen to incoming requests. Automatically appoints all handlers and maps everything.

	```php
	run(): Slim\App
	```

---

### HandlerCaller

All mocking methods return the text output that would've been sent to the browser. This is a JSON string most of the
times.

- #### Create a HandlerCaller

	Create a caller for `$handlerClass`. You can leave $baseURL empty but for consistency and compatibility you should set
	this to the base URL this handler would've listened to (without the route URL).

	```php
	$caller = new HandlerCaller(string $baseURL, string $handlerClass);
	```

- #### GET

	Mock a GET request to `$url` with `$headers`.

	```php
	get(string $url, array $headers = []): string
	```

- #### POST

	Mock a POST request to `$url` with `$headers` and send `$body` with it. If `$body` is an array, it will be converted to
	Form or JSON, based on `Content-Type` in `$headers` (default is Form).

	```php
	post(string $url, array $headers, mixed $body): string
	```

- #### PUT

	Mock a PUT request to `$url` with `$headers` and send `$body` and `$files` with it. If `$body` is an array, it will be
	converted to Form or JSON, based on `Content-Type` in `$headers` (default is Form).

	```php
	put(string $url, array $headers, mixed $body, array $files = []): string
	```

- #### PATCH

	Same as POST, just with PATCH as HTTP method and `$files`.

	```php
	patch(string $url, array $headers, mixed $body, $files = []): string
	```

- #### DELETE

	Same as POST, just with DELETE as HTTP method.

	```php
	delete(string $url, array $headers, mixed $body): string
	```

---

### Handler

To have your API do something, you need to create handlers which extend `Adepto\Slim3Init\Handlers\Handler`. Each
handler must override `getRoutes(): array` to return an array of routes. Each handler receives a container in the
constructor by default.

The actual methods of your handler must have the following signature:

```php
public function someName(Adepto\Slim3Init\Request $request, Adepto\Slim3Init\Response $response, \stdClass $args): Adepto\Slim3Init\Response
```

---

### PrivilegedHandler

Same as for Handler, only that this type of handler also has to
override `actionAllowed(string $action, array $data = []): bool` to determine, if a given action is allowed and
permitted. A PrivilegedHandler has an authorization client (client used to authenticate, instance
of `Adepto\Slim3Init\Client\Client`) via `getClient()`.

```php
forcePermission(string $action, array $data = []): bool
```

Force a permission. This is basically just an alias for `actionAllowed` (which you have to override) but throws
a `Adepto\Slim3Init\Exceptions\AccessDeniedException` if the given permission is not allowed.

---

### Route

Defines a route which has to be returned inside an array returned by your handler's `getRoutes()` function.

```php
new Route(string $httpMethod, string $url, string $classMethod, array $arguments = [], string $name = '')
```

- `$httpMethod`: The HTTP verb used for this route, i.e. GET, POST, PATCH, ...
- `$url`: Slim-compatible URL pattern, i.e. `/client/{client:[a-zA-Z0-9]+}`
- `$classMethod`: The name of the method to be called in the handler.
- `$arguments`: Additional arguments to add to `$args` of the method.
- `$name`: Name for the route so that it can be retrieved by any handlers.

---

### Interface: Client

-
	```php
	getUsername(): string
	```

	Should return the username of the currently logged in user (if `BasicAuth` was used).

-
	```php
	getPermissions(): array
	```

	Should return an array full of `Adepto\Slim3Init\Client\Permission` objects for the currently logged in user (
	if `BasicAuth` was used).

-
	```php
	hasPermission(string $name, array $data = []): bool;
	```

	Should return true when the currently logged in user has a certain permission. You can use `$data` to combine the
	permission with more info, i.e. when a resource's information access should be constrained to certain IDs.

---

### Interface: Permission

-
	```php
	getName(): string
	```

	Should return the name of the permission. You are free to define how a name looks like. It is recommended to use
	reverse-domain style, i.e. `adepto.api.addKnowledge`.

-
	```php
	getData(): array
	```

	Should return information specific to that permission, i.e. IDs of a resource that can be accessed. Can be an empty
	array, if there is no information.

-
	```php
	isAllowed(): bool
	```

	Should return true, if the permission is allowed.

---

### Abstract: BasicAuth (Middleware)

-
	```php
	authorize(array $credentials): array
	```

	Should return an array with more information to be added to the container, i.e. an authorized client to be used with a
	PrivilegedHandler. If you're going to return a client, make sure to set the key to `PrivilegedHandler::CONTAINER_CLIENT`
	. Should throw an `Adepto\Slim3Init\Exceptions\UnauthorizedException` if the user could not be authorized.

---

## Examples

Examples can be found in `examples/` of this repository.

---

# Upgrade from SlimInit 1.0 (using Slim3)

While quite a lot has changed under the hood in Slim, the actual effects on SlimInit are as minimal as possible. There
are 3 breaking changes and a few minor changes.

## Breaking Changes

### 1. Handlers now must return an instance of `Adepto\Slim3Init\Response`

Previously, all handlers were defined using only Psr7-compatible interfaces. While you can still define your handler's
arguments using Psr7, the return value must definitely be an instance of `Adepto\Slim3Init\Response`. If you need to
convert an existing response, use `Response::fromSlimResponse($originalResponse)`.

### 2. Middleware handling changes from a callback `$next()` to a handler

This change comes directly from Slim4, as SlimInit does not change this behavior. Previously, middleware worked like
this:

```php
<?php
/* Slim3 */
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class YourMiddleware {
	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, Callable $next): ResponseInterface {
		// Something before others run
		$newResponse = $next($request, $response);
		// Code after others have run
		return $newResponse;
	}
}
```

Now, middleware uses a `RequestHandlerInterface` to process other code:

```php
<?php
/* Slim4 */
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Adepto\Slim3Init\Request;

class YourMiddleware {
	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function __invoke(Request $request, RequestHandlerInterface $handler): ResponseInterface {
		// Something before others run
		$response = $handler->handle($request);
		// Code after others have run
		return $response;
	}
}
```

**You no longer have access to the response before other middleware and handlers have run.**

### 3. `SlimInit` no longer contains `handleError`, `handleNotFound` and `handleMethodNotAllowed`

Instead of overriding these methods in your own application to customize the handling of 500, 404 and 405 respectively,
you now implement your own `ExceptionHandler` and assign it to exceptions. Example:

```php
<?php
use Adepto\Slim3Init\SlimInit;
use Adepto\Slim3Init\Request;
use Adepto\Slim3Init\Handlers\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;

class NotFoundHandler extends ExceptionHandler {

	public function handle(Request $request, Throwable $t, bool $displayDetails): ResponseInterface {
		return $this->createResponse(404)
		            ->withJson(['error' => 'not_found']);
	}
}

// … your $app definition

/** @var $app SlimInit */
$app->setException(SomethingNotFoundException::class, NotFoundHandler::class);
```

You can also override already existing default handlers for 404, 405 and 500:

```php
use Adepto\Slim3Init\Exceptions\InternalErrorException;
use Adepto\Slim3Init\Exceptions\MethodNotAllowedException;
use Adepto\Slim3Init\Exceptions\NotFoundException;
use Adepto\Slim3Init\SlimInit;
/** @var $app SlimInit */

// Customize 404
$app->setException(NotFoundException::class, CustomHandler::class);
// Customize 405
$app->setException(MethodNotAllowedException::class, CustomHandler::class);
// Customize default handler (500)
$app->setException(InternalErrorException::class, CustomHandler::class);
// Customize all at once
$app->setException([
	NotFoundException::class,
	MethodNotAllowedException::class,
	InternalErrorException::class
], CustomHandler::class);
```

To use the default exception handler and just customize the HTTP status code, you can continue to assign a status code
instead of a handler, just like in SlimInit 1.x:

```php
use Adepto\Slim3Init\SlimInit;
// … your $app definition
/** @var $app SlimInit */
$app->setException(SomethingNotFoundException::class, 404);
```

## Minor Changes

### 1. SlimInit now uses a custom extension of `DI\Container`

It is still compatible with Psr7 `ContainerInterface`. If you specify `Adepto\Slim3Init\Container` as the type, you can
make use of ArrayAccess without exceptions, like so:

```php
/** @var $container \Adepto\Slim3Init\Container */
// Get value like normal, with exception if key was not found
$value = $container->get('some-value');
// Get value array-style, with null being returned if key was not found
$value = $container['some-value'];
```

### 2. Slim's convenience methods `withJSON` and `write` are now a custom implementation on `Response`

In their pursuit of being the most generic library on earth, getting Slim's convenience methods to work on top of
`ResponseInterface` that doesn't have them and still have IDEs pick that up correctly is a nightmare. So SlimInit
contains its own implementation of those.

### 3. A new abstract class `Middleware`

You can now also supply middleware that extends `Adepto\Slim3Init\Middleware\Middleware`. This gives you some convenience
like always having access to the container and creating responses.

```php
use Adepto\Slim3Init\Middleware\Middleware;
use Adepto\Slim3Init\Request;
use Adepto\Slim3Init\Response;
use Psr\Http\Server\RequestHandlerInterface;

class YourMiddleware extends Middleware {

	public function __invoke(Request $request, RequestHandlerInterface $handler) : Response{
		return $this->createResponse(404);
	}
}
```

### 4. `new HandlerCaller(…)` is deprecated

Use `HandlerCaller::default(string $baseURL, string $handlerClass, $container = null)` instead.

### to be continued