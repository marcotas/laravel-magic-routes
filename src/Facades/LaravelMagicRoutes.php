<?php

namespace MarcoT89\LaravelMagicRoutes\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelMagicRoutes extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelmagicroutes';
    }
}
