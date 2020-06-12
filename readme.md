# LaravelMagicRoutes

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

<!-- [![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci] -->

Create RESTfull routes for your laravel app based on your controller names and namespaces to avoid overrides and define a pattern to your projects routes. It is compatible with laravel 7+.

## Installation

Via Composer

```bash
$ composer require marcot89/laravel-magic-routes
```

## Usage

Add `MagicRoutes` trait to the controller to generate routes for it automatically:

```php
namespace App\Http\Controllers;

use MarcoT89\LaravelMagicRoutes\Traits\MagicRoutes;

class UserController extends Controller
{
    use MagicRoutes;
}
```

For now there is no route for your controller because you don't have any `public` method declared on it. A **new route** will be generated for every **public method** added to the controller. So if we add the common crud public methods like this:

```php
namespace App\Http\Controllers;

class UserController extends Controller
{
    use MagicRoutes;

    public function index(...) {...}
    public function store(...) {...}
    public function update(...) {...}
    public function create(...) {...}
    public function show(...) {...}
    public function edit(...) {...}
    public function destroy(...) {...}
    public function forceDestroy(...) {...}
}
```

We will have these generated routes based on those public methods:

```
| GET|HEAD | users                      | users.index          | App\Http\Controllers\UserController@index          |
| POST     | users                      | users.store          | App\Http\Controllers\UserController@store          |
| GET|HEAD | users/create               | users.create         | App\Http\Controllers\UserController@create         |
| PUT      | users/{user}               | users.update         | App\Http\Controllers\UserController@update         |
| GET|HEAD | users/{user}               | users.show           | App\Http\Controllers\UserController@show           |
| DELETE   | users/{user}               | users.destroy        | App\Http\Controllers\UserController@destroy        |
| GET|HEAD | users/{user}/edit          | users.edit           | App\Http\Controllers\UserController@edit           |
| DELETE   | users/{user}/force-destroy | users.force-destroy  | App\Http\Controllers\UserController@forceDestroy   |
```

### \# Customize Http Methods for Routes

Any other public method in the controller will be generated a route with a GET http method as default, but you can customize it with a prefix. Let's see an example.

```php
class PostController extends Controller
{
    use MagicRoutes;

    // Generated route:
    // GET /posts/{post}/publish
    public function publish(Post $post) {...}

    // Generated route:
    // POST /posts/{post}/publish
    public function postPublish(Post $post) {...}

    // Generated route:
    // PUT /posts/{post}/publish
    public function putPublish(Post $post) {...}

    // Generated route:
    // DELETE /posts/{post}/publish
    public function deletePublish(Post $post) {...}
}
```

### \# Route Params

By convention the first parameter will be set before the action name. All other parameters will be added after.

```php
class PostController extends Controller
{
    public function publish(Post $post, $one, $two, $three) {...}
}
```

Will generate:

```
GET /posts/{post}/publish/{one}/{two}/{three}
```

### \# Middlewares

There are two ways to declare a middleware for a controller:

**Using Protected Property**

```php
class UserController extends Controller
{
    use MagicRoutes;

    // use a string for one middlware
    protected $middleware = 'auth';
    // or use an array for many middlewares
    protected $middleware = [
        'auth',
        'verified' => ['except' => ['index', 'edit', 'update']],
    ];
}
```

**Using Constructor**

```php
class UserController extends Controller
{
    use MagicRoutes;

    public function __construct()
    {
        $this->middleware('auth');
    }
}
```

### \# Namespaced Routes

The controller namespace will generate a prefix for that route:

```php
namespace App\Http\Controllers\Api\V1;

...

class UserController extends Controller
{
    use MagicRoutes;

    public function index(...) {...}
}
```

Any public method declared in this controller will generate urls with prefix like:

```
/api/v1/users
```

And named routes like:

```
api.v1.users
```

### \# Invokable Controllers

If you like to create a controller for every action you can use the invokable controllers in a namespaced that makes sense for your route. Example:

```php
namespace App\Http\Controllers\Posts;

class PublishController extends Controller
{
    use MagicRoutes;

    protected $middleware = 'auth';
    protected $method = 'post'; // only works for invokable controllers
    // or
    protected $method = 'post|put'; // separate by pipe for more http methods

    public function __invoke(Post $post) {...}
}
```

This will generate an URL like:

```
POST /posts/publish/{post} -> name: posts.publish
```

### \# Nested RESTfull Routes

Sometimes you want or need to define nested resources. Let's say we have posts of a user but we want nested routes for that. You can do it with a namespaced controller like this:

```php
namespace App\Http\Controllers\Users; // Note that it is inside Users namespace for prefix route

class PostController extends Controller
{
    use MagicRoutes;

    protected $prefix = '{user}';

    public function index(User $user) {
        return $user->posts()->paginate();
    }
}
```

This will generate the following route:

```
GET /users/{user}/posts -> name: users.posts.index
```

### \# Resource URLs Plural vs Singular

RESTfull resources is always in plural. So the convention defines URL resources in plural no matter the name of the controller. If your controller is `UserController` or `UsersController` both will generate the same resource URL `/users`.

But you can disable this behavior using the `plural` property:

```php
class UserController extends Controller
{
    use MagicRoutes;

    protected $plural = false;

    ...
}
```

With this property `false` will generate routes following the controller name. Now if your controller is `UserController` it will generate a route `/user`. If it is `UsersController` it will generate `/users`.

> **Note:** For invokable controllers the plural property is always disabled.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

<!-- ## Testing

```bash
$ composer test
``` -->

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

-   [Marco Avila][link-author]
-   [All Contributors][link-contributors]

## MIT License

Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/marcot89/laravel-magic-routes.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/marcot89/laravel-magic-routes.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/marcot89/laravel-magic-routes/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield
[link-packagist]: https://packagist.org/packages/marcot89/laravel-magic-routes
[link-downloads]: https://packagist.org/packages/marcot89/laravel-magic-routes
[link-travis]: https://travis-ci.org/marcot89/laravel-magic-routes
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/marcot89
[link-contributors]: ../../contributors
