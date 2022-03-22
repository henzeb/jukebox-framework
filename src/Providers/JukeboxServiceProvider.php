<?php

namespace Henzeb\Jukebox\Providers;

use Illuminate\Support\ServiceProvider;

class JukeboxServiceProvider extends ServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../../config/jukebox.php';

    public function boot()
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'jukebox');

        $this->publishes(
            [
                self::CONFIG_PATH => config_path('jukebox.php'),
            ],
            'jukebox'
        );
    }
}
