<?php

namespace Henzeb\Jukebox\Observers;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;

abstract class JukeboxCrawlObserver
{
    /**
     * Called when the crawler will crawl the url.
     *
     * @param UriInterface $url
     */
    public function willCrawl(UriInterface $url): void
    {
    }

    /**
     * Called when the crawler has crawled the given url successfully.
     *
     * @param JukeboxCrawlUrl $jukeboxCrawlUrl
     * @param ResponseInterface $response
     */
    abstract public function crawled(
        JukeboxCrawlUrl   $jukeboxCrawlUrl,
        ResponseInterface $response
    ): void;

    /**
     * Called when the crawler had a problem crawling the given url.
     *
     * @param JukeboxCrawlUrl $jukeboxCrawlUrl
     * @param RequestException $requestException
     */
    public function crawlFailed(
        JukeboxCrawlUrl  $jukeboxCrawlUrl,
        RequestException $requestException
    ): void
    {
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling(): void
    {
    }
}
