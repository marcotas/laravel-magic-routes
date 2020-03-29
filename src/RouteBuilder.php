<?php

namespace MarcoT89\LaravelMagicRoutes;

use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

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
        $this->method = $method;
        $this->parameters = $this->sanitizeParameters($method->getParameters());
    }

    public function register()
    {
        $this->createBaseUrl()
            ->createActionUrl();


        Route::{$this->httpMethod()}($this->route, [$this->getClass(), $this->method->name])->name($this->getRouteName());
    }

    private function createBaseUrl()
    {
        $this->route = $this->createControllerBaseUrl();

        return $this;
    }

    private function createActionUrl()
    {
        $routeParams = $this->createRouteParameters();
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

    private function suffix()
    {
        if ($this->isCrudAction()) {
            return $this->getCrudActions()->get($this->method->name)->suffix
                ? $this->getActionName() : '';
        }

        return $this->getActionName();
    }

    private function httpMethod()
    {
        if ($this->isCrudAction()) {
            return $this->getCrudActions()->get($this->method->name)->verb;
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

        return Str::of($this->method->name)
            ->replace($httpMethod, '')
            ->kebab()
            ->__toString();
    }

    private function isCrudAction()
    {
        return $this->getCrudActions()->keys()->contains($this->method->name);
    }

    private function getCrudActions(): Collection
    {
        return collect([
            'index' => (object) [
                'verb' => 'get',
                'suffix' => false,
            ],
            'store' => (object) [
                'verb' => 'post',
                'suffix' => false,
            ],
            'update' => (object) [
                'verb' => 'put',
                'suffix' => false,
            ],
            'show' => (object) [
                'verb' => 'get',
                'suffix' => false,
            ],
            'destroy' => (object) [
                'verb' => 'delete',
                'suffix' => false,
            ],
            'create' => (object) [
                'verb' => 'get',
                'suffix' => true,
            ],
            'edit' => (object) [
                'verb' => 'get',
                'suffix' => true,
            ],
            'forceDestroy' => (object) [
                'verb' => 'delete',
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

        $prefixArray = collect(explode(
            '/',
            Str::of($namespacedController)
                ->replace('\\', '/')
                ->__toString()
        ))
            ->map(fn ($name) => Str::of($name)->snake()->slug()->__toString());

        $prefixArray->pop();
        $prefix = $prefixArray->join('/');

        return $prefix ? "$prefix/$baseUrl" : $baseUrl;
    }

    private function getResourceUrl()
    {
        return Str::of($this->getClass())
            ->afterLast('\\')
            ->beforeLast('Controller')
            ->snake()
            ->slug()
            ->plural()
            ->__toString();
    }

    private function getRouteName()
    {
        $baseName = Str::of($this->createControllerBaseUrl())->replace('/', '.');
        $routeName = Str::of($this->getActionName());

        return "$baseName.$routeName";
    }

    private function getClass()
    {
        return get_class($this->controller);
    }
}
