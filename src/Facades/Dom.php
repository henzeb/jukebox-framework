<?php

namespace Henzeb\Jukebox\Facades;

use Illuminate\Support\Facades\Facade;
use Henzeb\Jukebox\Factories\DomFactory;

/**
 * @mixin DomFactory
 */
class Dom extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DomFactory::class;
    }
}
