<?php

namespace MarcoT89\LaravelMagicRoutes;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;

class RouteBuilder
{
    protected $namespace = "App\Http\Controllers";
    /** @var Controller */
    protected $controller;

    /** @var ReflectionMethod */
    protected $method;

    /** @var string */
    public $route;

    /** @var Collection */
    protected $parameters;

    protected $httpMethods = ['get', 'put', 'post', 'delete', 'patch'];

    public function __construct(Controller $controller, ReflectionMethod $method)
    {
        $this->controller = $controller;
        $this->method     = $method;
        $this->parameters = $this->sanitizeParameters($method->getParameters());
    }

    public function register()
    {
        $this->createBaseUrl()
            ->createActionUrl()
            ->sanitizeRoute();

        $actionMap = [$this->getClass(), $this->method->name];

        if ($this->isInvokable()) {
            $actionMap = $this->getClass();
        }

        Route::match($this->httpMethod(), $this->route, $actionMap)
            ->name($this->getRouteName());
    }

    private function createBaseUrl()
    {
        $this->route = $this->createControllerBaseUrl();

        return $this;
    }

    private function createActionUrl()
    {
        $routeParams = $this->createRouteParameters()->filter(function ($param) {
            return !Str::contains($this->route, $param);
        });

        $firstParam = $routeParams->shift();

        $this->route .= $firstParam ? "/$firstParam" : '';

        if ($suffix = $this->suffix()) {
            $this->route .= "/$suffix";
        }

        if ($routeParams->isNotEmpty()) {
            $this->route .= "/{$routeParams->join('/')}";
        }

        return $this;
    }

    private function sanitizeRoute()
    {
        $this->route = Str::of($this->route)
            ->trim('/')
            ->replace('//', '/');

        return $this;
    }

    private function suffix()
    {
        if ($this->isCrudAction()) {
            return $this->getCrudActions()->get($this->method->name)->suffix
                ? $this->getActionName() : '';
        }

        if ($this->isInvokable()) {
            return '';
        }

        return $this->getActionName();
    }

    private function httpMethod()
    {
        if ($this->isCrudAction()) {
            return $this->getCrudActions()->get($this->method->name)->verb;
        }

        if ($this->isInvokable() && $this->getProperty('method')) {
            return Str::of($this->getProperty('method'))->lower()->explode('|')->toArray();
        }

        return $this->getHttpMethodFromMethodName() ?? 'get';
    }

    private function getHttpMethodFromMethodName()
    {
        return collect($this->httpMethods)->map(function ($httpMethod) {
            return Str::of($this->method->name)
                ->kebab()
                ->startsWith("$httpMethod-")
                ? $httpMethod : null;
        })->filter()->first();
    }

    private function getActionName()
    {
        $httpMethod = $this->getHttpMethodFromMethodName();

        $action = Str::of($this->method->name)
            ->replace($httpMethod, '')
            ->kebab();

        return $action->__toString();
    }

    private function isCrudAction($method = null)
    {
        $method = $method ?? $this->method->name;

        return $this->getCrudActions()->keys()->contains($method);
    }

    private function getCrudActions(): Collection
    {
        return collect([
            'index' => (object) [
                'verb'   => 'get',
                'suffix' => false,
            ],
            'store' => (object) [
                'verb'   => 'post',
                'suffix' => false,
            ],
            'update' => (object) [
                'verb'   => 'put',
                'suffix' => false,
            ],
            'show' => (object) [
                'verb'   => 'get',
                'suffix' => false,
            ],
            'destroy' => (object) [
                'verb'   => 'delete',
                'suffix' => false,
            ],
            'create' => (object) [
                'verb'   => 'get',
                'suffix' => true,
            ],
            'edit' => (object) [
                'verb'   => 'get',
                'suffix' => true,
            ],
            'forceDestroy' => (object) [
                'verb'   => 'delete',
                'suffix' => true,
            ],
        ]);
    }

    private function createRouteParameters(): Collection
    {
        return $this->parameters
            ->map(function (ReflectionParameter $parameter) {
                return "{{$parameter->name}}";
            });
    }

    private function sanitizeParameters($parameters): Collection
    {
        return collect($parameters)
            ->filter(function (ReflectionParameter $parameter) {
                $class = $parameter->getClass();

                if (!$class) {
                    return true;
                }

                return $class->name !== Request::class
                    && !$class->isSubclassOf(Request::class);
            });
    }

    private function createControllerBaseUrl()
    {
        $namespacedController = Str::of(get_class($this->controller))
            ->after($this->namespace)
            ->trim('\\')
            ->__toString();

        $baseUrl = $this->getResourceUrl();

        $namespacePrefixArray = collect(explode(
            '/',
            Str::of($namespacedController)
                ->replace('\\', '/')
                ->__toString()
        ))
            ->map(function ($name) {
                return Str::of($name)->snake()->slug()->__toString();
            });

        $namespacePrefixArray->pop();
        $namespacePrefix = $namespacePrefixArray->join('/');

        $prefix = $this->getProperty('prefix');

        if ($prefix && $baseUrl) {
            $baseUrl = "$prefix/$baseUrl";
        }

        return $namespacePrefix ? "$namespacePrefix/$baseUrl" : $baseUrl;
    }

    private function getResourceUrl()
    {
        $resourceUrl = Str::of($this->getClass())
            ->afterLast('\\')
            ->beforeLast('Controller')
            ->snake()
            ->slug();

        if ($this->shouldBePlural()) {
            $resourceUrl = $resourceUrl->plural();
        }

        if ($this->isCrudAction($resourceUrl)) {
            return '';
        }

        return $resourceUrl->__toString();
    }

    private function getRouteName()
    {
        $baseName = Str::of($this->route);

        $this->createRouteParameters()->each(function ($param) use (&$baseName) {
            $baseName = $baseName->replace($param, '')->replace('//', '/');
        });

        $baseName = $baseName->trim('/')->replace('/', '.');

        $routeName = $this->getActionName();

        if ($baseName->endsWith($routeName) || $routeName === '__invoke') {
            return $baseName->__toString();
        }

        return "$baseName.$routeName";
    }

    private function getClass()
    {
        return get_class($this->controller);
    }

    private function getProperty($name)
    {
        $controller = new ReflectionObject($this->controller);

        $property = $controller->hasProperty($name) ? $controller->getProperty($name) : null;

        if (!$property) {
            return null;
        }

        $property->setAccessible(true);

        return $property->getValue($this->controller);
    }

    private function isInvokable()
    {
        return method_exists($this->controller, '__invoke');
    }

    private function shouldBePlural()
    {
        $shouldBePlural = $this->getProperty('plural') ?? true;

        return $this->isInvokable() ? false : $shouldBePlural;
    }
}
