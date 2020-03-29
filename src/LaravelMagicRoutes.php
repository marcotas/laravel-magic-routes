<?php

namespace MarcoT89\LaravelMagicRoutes;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionObject;
use Symfony\Component\Finder\SplFileInfo;

class LaravelMagicRoutes
{
    public static function boot()
    {
        (new static)->getControllers()->map(function (\Illuminate\Routing\Controller $controller) {
            $reflectionClass = new ReflectionObject($controller);

            $registerRoutes = $reflectionClass->getMethod('registerRoutes');
            $registerRoutes->setAccessible(true);
            $registerRoutes->invoke($controller);
        });
    }

    private function getControllers(): Collection
    {
        $files = collect(File::allFiles(app_path('Http/Controllers')));

        return $files->map(function (SplFileInfo $file) {
            $controllerClass = Str::of($file->getRealPath())
                ->replace(app_path(), '')
                ->replace('/', '\\')
                ->replace('.php', '')
                ->prepend('App');
            /** @var \Illuminate\Routing\Controller $controller */
            return resolve($controllerClass->__toString());
        })->filter(fn (\Illuminate\Routing\Controller $controller) => method_exists($controller, 'registerRoutes'));
    }
}
