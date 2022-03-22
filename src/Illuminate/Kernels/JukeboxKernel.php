<?php

namespace Henzeb\Jukebox\Illuminate\Kernels;

use Illuminate\Console\Application;
use Illuminate\Foundation\Console\Kernel;
use Henzeb\Jukebox\Factories\JukeboxFactory;
use function tap;

class JukeboxKernel extends Kernel
{
    protected function getArtisan()
    {
        return tap(
            parent::getArtisan(),
            function (Application $artisan) {

                $artisan->setName(JukeboxFactory::JUKEBOX_NAME);

                $artisan->setVersion(JukeboxFactory::JUKEBOX_VERSION);
            }
        );
    }
}
