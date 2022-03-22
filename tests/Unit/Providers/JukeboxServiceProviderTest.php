<?php

namespace Henzeb\Jukebox\Tests\Unit\Common\Providers;

use Mockery;
use Orchestra\Testbench\TestCase;
use Henzeb\Jukebox\Providers\JukeboxServiceProvider;

class JukeboxServiceProviderTest extends TestCase
{
    public function testShouldRegisterCorrectly(): void
    {
        $serviceProvider = Mockery::mock(JukeboxServiceProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $serviceProvider->expects('mergeConfigFrom')->with(JukeboxServiceProvider::CONFIG_PATH, 'jukebox');

        $serviceProvider->expects('publishes')->with([
            JukeboxServiceProvider::CONFIG_PATH => config_path('jukebox.php'),
        ], 'jukebox');

        $serviceProvider->boot();
    }
}
