<?php

namespace Henzeb\Jukebox\Demo;


use GuzzleHttp\Psr7\Uri;
use Henzeb\Jukebox\Facades\DB;
use Psr\Http\Message\UriInterface;
use Henzeb\Jukebox\Facades\Jukebox;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Henzeb\Jukebox\Demo\Profiles\BaseProfile;
use Henzeb\Jukebox\Demo\Profiles\PeopleProfile;
use Henzeb\Jukebox\Demo\Observers\BaseObserver;
use Henzeb\Jukebox\Demo\Observers\PeopleObserver;
use Henzeb\Jukebox\Demo\Observers\PeopleImageObserver;
use Henzeb\Jukebox\Console\Commands\Contracts\JukeboxCommand;

class DemoCommand extends JukeboxCommand
{
    public function signature(): string
    {
        return 'people:demo';
    }

    protected function description(): string
    {
        return 'Demonstrates Jukebox by crawling people.php.net';
    }

    public function url(): UriInterface
    {
        return new Uri(
            'https://people.php.net/?page=1'
        );
    }

    public function jukebox(): JukeboxManager
    {
        $this->setLimit(1);

        $jukebox = Jukebox::create();

        $jukebox->add(
            new BaseProfile($this->url()),
            new BaseObserver($jukebox)
        )->add(
            new PeopleProfile($this->url()),
            [
                new PeopleObserver(),
                new PeopleImageObserver()
            ]
        );

        return $jukebox;
    }
}
