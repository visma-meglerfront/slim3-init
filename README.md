# Slim3 Init

This is a very convenient wrapper around the Slim3 framework providing us with some shortcuts and prepopulated concepts.

## Installation

Add this to `composer.json`:

```json
"repositories": [
	{
		"type": "vcs",
		"url": "https://github.com/adeptoas/slim3-init"
	}
],

"require": {
	"adeptoas/slim3-init": "^1.0.0"
}
```

Make sure to merge your `require`-blocks!

## Usage

### SlimInit

```php
__construct
```

Empty.

```php
setDebugHeader(string $header, string $expectedValue = ''): SlimInit
```

Set a header which will trigger debug mode to show more information on errors. Leave empty to disable this feature.

```php
setException(string $exception, int $statusCode): SlimInit
```

Map an exception to a HTTP status code.

```php
setException(array $exception, int $statusCode): SlimInit
```

Map multiple exceptions to an HTTP status code.

```php
addToContainer(string $key, mixed $value): SlimInit
```

Add something to the Slim container.

```php
addHandler(string $className): SlimInit
```

Add a specific handler. Must be the name of a class that extends `Adepto\Slim3Init\Handlers\Handler`.

```php
addHandlersFromDirectory(string $dir): SlimInit
```

Add all handlers from a specific directory. Non-recursive and the filenames must be the class names followed by `.php`.

```php
addMiddleware(callable $middleware): SlimInit
```

Add some Slim-compatible middleware. Refer to Slim's documentation for more information about middleware.

```php
run(): Slim\App
```

Boot up the application and listen to incoming requests. Automatically appoints all handlers and maps everything.

### HandlerCaller

All mocking methods return the text output that would've been sent to the browser. This is a JSON string most of the times.

```php
__construct(string $baseURL, string $handlerClass)
```

Create a caller for `$handlerClass`. You can leave $baseURL empty but for consistency and compatibility you should set this to the base URL this handler would've listened to (without the route URL).

```php
get(string $url, array $headers = []): string
```

Mock a GET request to `$url` with `$headers`.

```php
post(string $url, array $headers, mixed $body): string
```

Mock a POST request to `$url` with `$headers` and send `$body` with it.
If `$body` is an array, it will be converted to Form or JSON, based on `Content-Type` in `$headers` (default is Form).

```php
put(string $url, array $headers, mixed $body, array $files = []): string
```

Mock a PUT request to `$url` with `$headers` and send `$body` and `$files` with it.
If `$body` is an array, it will be converted to Form or JSON, based on `Content-Type` in `$headers` (default is Form).

```php
patch(string $url, array $headers, mixed $body, $files = []): string
```

Same as POST, just with PATCH as HTTP method and `$files`.

```php
delete(string $url, array $headers, mixed $body): string
```

Same as POST, just with DELETE as HTTP method.

### Handler

To have your API do something, you need to create handlers which extend `Adepto\Slim3Init\Handlers\Handler`. Each handler must override `getRoutes(): array` to return an array of routes. Each handler is given a Slim container in the constructor by default.

The actual methods of your handler must have the following signature:
	
```php
public function someName(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, \stdClass $args): Psr\Http\Message\ResponseInterface
```

### PrivilegedHandler

Same as for Handler, only that this type of handler also has to override `actionAllowed(string $action, array $data = []): bool` to determine, if a given action is allowed and permitted. A PrivilegedHandler has an authorization client (client used to authenticate, instance of `Adepto\Slim3Init\Client\Client`) via `getClient()`.

```php
forcePermission(string $action, array $data = []): bool
```

Force a permission. This is basically just an alias for `actionAllowed` (which you have to override) but throws a `Adepto\Slim3Init\Exceptions\AccessDeniedException` if the given permission is not allowed.

### Route

Defines a route which has to be returned inside an array returned by your handler's `getRoutes()` function.

```php
__construct(string $httpMethod, string $url, string $classMethod)
```

- `$httpMethod`: The HTTP verb used for this route, i.e. GET, POST, PATCH, ...
- `$url`: Slim-compatible URL pattern, i.e. `/client/{client:[a-zA-Z0-9]+}`
- `$classMethod`: The name of the method to be called in the handler.

### Interface: Client

```php
getUsername(): string
```

Should return the username of the currently logged in user (if `BasicAuth` was used).

```php
getPermissions(): array
```

Should return an array full of `Adepto\Slim3Init\Client\Permission` objects for the currently logged in user (if `BasicAuth` was used).

```php
hasPermission(string $name, array $data = []): bool;
```

Should return true when the currently logged in user has a certain permission. You can use `$data` to combine the permission with more info, i.e. when a resource's information access should be constrained to certain IDs.

### Interface: Permission

```php
getName(): string
```

Should return the name of the permission. You are free to define how a name looks like. It is recommended to use reverse-domain style, i.e. `adepto.api.addKnowledge`.

```php
getData(): array
```

Should return information specific to that permission, i.e. IDs of a resource that can be accessed. Can be an empty array, if there is no information.

```php
isAllowed(): bool
```

Should return true, if the permission is allowed.

### Abstract: BasicAuth (Middleware)

```php
authorize(array $credentials): array
```

Should return an array with more information to be added to Slim's container, i.e. an authorized client to be used with a PrivilegedHandler. If you're going to return a client, make sure to set the key to `PrivilegedHandler::CONTAINER_CLIENT`. Should throw an `Adepto\Slim3Init\Exceptions\UnauthorizedException` if the user could not be authorized.

## Examples

Examples can be found in `examples/` of this repository.
