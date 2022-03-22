<?php

namespace Henzeb\Jukebox\Demo\Observers;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Henzeb\Jukebox\Facades\Dom;
use Psr\Http\Message\ResponseInterface;
use Henzeb\Jukebox\Facades\Console;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

class PeopleImageObserver extends CrawlObserver
{
    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {
        $dom = Dom::from($response);

        $image = $dom->filter('img[rel="foaf:img"]')->first()->extract(['src']);
        $image = new Uri($image[0]);
        $image = $image->withScheme('https');

        Console::info(sprintf('oh, and his image is located here: %s', $image), 'vv');
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null): void
    {
        Console::error(sprintf('failed: %s',$url));
    }
}
