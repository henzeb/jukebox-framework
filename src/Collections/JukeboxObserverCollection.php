<?php

namespace Henzeb\Jukebox\Collections;

use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Henzeb\Jukebox\Observers\JukeboxCrawlObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;

class JukeboxObserverCollection extends CrawlObserverCollection
{
    public function __construct(JukeboxCrawlObserver|JukeboxObserverCollection ...$observers)
    {
        parent::__construct($observers);
    }

    public function willCrawl(UriInterface $url): void
    {
        foreach ($this->observers as $observer) {
            $observer->willCrawl($url);
        }
    }

    public function crawled(CrawlUrl|UriInterface $crawlUrl, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {
        $crawlUrl = $this->castToCrawlUrl($crawlUrl, $foundOnUrl);

        foreach ($this->observers as $crawlObserver) {
            $crawlObserver->crawled(
                $crawlUrl,
                $response,
            );
        }
    }

    public function crawlFailed(CrawlUrl|UriInterface $crawlUrl, RequestException $exception, ?UriInterface $foundOnUrl = null): void
    {
        $crawlUrl = $this->castToCrawlUrl($crawlUrl, $foundOnUrl);

        foreach ($this->observers as $crawlObserver) {
            $crawlObserver->crawlFailed(
                $crawlUrl,
                $exception,
            );
        }
    }

    private function castToCrawlUrl(CrawlUrl|UriInterface $crawlUrl, ?UriInterface $foundOnUrl): JukeboxCrawlUrl
    {
        if ($crawlUrl instanceof JukeboxCrawlUrl) {
            return $crawlUrl;
        }

        if ($crawlUrl instanceof CrawlUrl) {
            return JukeboxCrawlUrl::from($crawlUrl);
        }

        return JukeboxCrawlUrl::create($crawlUrl, $foundOnUrl);
    }

    public function finishedCrawling(): void
    {
        foreach ($this->observers as $observer) {
            $observer->finishedCrawling();
        }
    }

    private function set(mixed $offset, JukeboxCrawlObserver|JukeboxObserverCollection $value): void
    {
        if (is_null($offset)) {
            $this->observers[] = $value;
        } else {
            $this->observers[$offset] = $value;
        }
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }
}
