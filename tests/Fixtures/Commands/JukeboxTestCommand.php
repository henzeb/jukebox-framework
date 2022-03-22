<?php

namespace Henzeb\Jukebox\Tests\Fixtures\Commands;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Henzeb\Jukebox\Console\Commands\Contracts\JukeboxCommand;

abstract class JukeboxTestCommand extends JukeboxCommand
{
    protected function signature(): string
    {
        return 'jukebox:test';
    }

    protected function description(): string
    {
        return 'a random description';
    }

    protected function url(): UriInterface|RequestInterface
    {
        return new Uri('https://www.google.nl/');
    }

    public function jukebox(): JukeboxManager
    {
        return new JukeboxManager();
    }
}
