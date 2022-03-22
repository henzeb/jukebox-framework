<?php

namespace Henzeb\Jukebox\Tests\Unit\Illuminate\Kernels;

use Orchestra\Testbench\TestCase;
use Henzeb\Jukebox\Factories\JukeboxFactory;
use Henzeb\Jukebox\Illuminate\Applications\JukeboxApplication;

class JukeboxApplicationTest extends TestCase
{
    public function testGetVersionReturnsJukeboxVersion()
    {
        $app = $this->app->make(JukeboxApplication::class);

        $this->assertEquals(JukeboxFactory::JUKEBOX_VERSION, $app->version());
    }
}
