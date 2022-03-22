<?php

namespace Henzeb\Jukebox\Observers;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

class EmptyObserver extends JukeboxCrawlObserver
{
    public function crawled(JukeboxCrawlUrl $jukeboxCrawlUrl, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {

    }
}
