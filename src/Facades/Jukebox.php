<?php

namespace Henzeb\Jukebox\Facades;


use Illuminate\Support\Facades\Facade;
use Henzeb\Jukebox\Factories\JukeboxFactory;

/**
 * @mixin JukeboxFactory
 */
class Jukebox extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JukeboxFactory::class;
    }
}
