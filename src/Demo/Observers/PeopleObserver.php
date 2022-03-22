<?php

namespace Henzeb\Jukebox\Demo\Observers;


use Psr\Http\Message\UriInterface;
use Henzeb\Jukebox\Facades\Dom;
use Psr\Http\Message\ResponseInterface;
use Henzeb\Jukebox\Facades\Console;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

class PeopleObserver extends CrawlObserver
{
    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {
        $dom = Dom::from($response);

        $openbugs = $dom->filter('.profile-details li:nth-child(2)')->text();
        $username = $dom->filter('.profile-name h1')->text();

        Console::info(sprintf('%s has %s (%s)', $username, $openbugs, $foundOnUrl?->getQuery()));
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null): void
    {
        Console::error(sprintf('failed: %s',$url));
    }
}
