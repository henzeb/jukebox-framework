<?php

namespace Henzeb\Jukebox\Tests\Fixtures\Facades;


use Henzeb\Jukebox\Facades\Facade;

/**
 * @mixin TestObject
 */
class TestFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TestObject::class;
    }
}
