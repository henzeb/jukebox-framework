<?php

namespace Henzeb\Jukebox\Illuminate\Applications;

use Illuminate\Foundation\Application;
use Henzeb\Jukebox\Factories\JukeboxFactory;

class JukeboxApplication extends Application
{
    public function version()
    {
        return JukeboxFactory::JUKEBOX_VERSION;
    }
}
