<?php

namespace MarcoT89\LaravelMagicRoutes\Traits;

use App\MagicRoutes\RouteBuilder;
use ReflectionMethod;
use ReflectionObject;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;

trait MagicRoutes
{
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
