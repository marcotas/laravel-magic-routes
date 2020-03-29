<?php

namespace MarcoT89\LaravelMagicRoutes\Traits;

use ReflectionMethod;
use ReflectionObject;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use MarcoT89\LaravelMagicRoutes\RouteBuilder;

trait MagicRoutes
{
    public function __construct()
    {
        parent::__construct();
        $this->registerMiddleware();
    }

    protected function registerMiddleware()
    {
        $middlewares      = [];
        $this->middleware = is_string($this->middleware) ? [$this->middleware] : $this->middleware;
        foreach ($this->middleware as $middleware => $options) {
            if (!is_string($middleware)) {
                $middleware = $options;
                $options    = [];
            }
            $middlewares[] = compact('middleware', 'options');
        }
        $this->middleware = $middlewares;
    }

    protected function registerRoutes()
    {
        $this->getPublicMethods()->each(function (ReflectionMethod $method) {
            /** @var Controller $this */
            (new RouteBuilder($this, $method))->register();
        });
    }

    private function getPublicMethods(): Collection
    {
        return collect((new ReflectionObject($this))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(function (ReflectionMethod $method) {
                return $method->getDeclaringClass()->name == static::class;
            })
            ->filter(function (ReflectionMethod $method) {
                return !in_array($method->name, ['__construct']);
            });
    }
}
