<?php

namespace Henzeb\Jukebox\Tests\Unit\Illuminate\Kernels;

use ReflectionClass;
use Orchestra\Testbench\TestCase;
use Henzeb\Jukebox\Factories\JukeboxFactory;
use Henzeb\Jukebox\Illuminate\Kernels\JukeboxKernel;

class JukeboxKernelTest extends TestCase
{
    public function testGetArtisanReturnsConsoleApplicationWithJukeboxDetails()
    {
        $method = (new ReflectionClass(JukeboxKernel::class))
            ->getMethod('getArtisan');

        $kernel = $this->app->make(JukeboxKernel::class);

        $this->assertEquals(JukeboxFactory::JUKEBOX_NAME, $method->invoke($kernel)->getName());

        $this->assertEquals(JukeboxFactory::JUKEBOX_VERSION, $method->invoke($kernel)->getVersion());
    }
}
