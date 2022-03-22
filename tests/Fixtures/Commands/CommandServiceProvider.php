<?php

namespace Henzeb\Jukebox\Tests\Fixtures\Commands;

use Mockery;
use Illuminate\Support\ServiceProvider;
use Henzeb\Jukebox\Console\Commands\Contracts\JukeboxCommand;

class CommandServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(JukeboxCommand::class, function(){
            $command = Mockery::mock(JukeboxTestCommand::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();
            $command->__construct();
            return $command;
        });

        $this->commands([
            JukeboxCommand::class
        ]);
    }
}
