<?php

namespace Henzeb\Jukebox\Demo\Observers;


use Psr\Http\Message\UriInterface;
use Henzeb\Jukebox\Facades\Console;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

class BaseObserver extends CrawlObserver
{
    public function __construct(private JukeboxManager $crawler)
    {
    }

    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {
        if ($this->crawler->getCurrentCrawlLimit()) {
            $this->crawler->setCurrentCrawlLimit($this->crawler->getCurrentCrawlLimit() + 1);
        }
        Console::info(sprintf('crawled: %s', $url), 'v');
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null): void
    {
        Console::error(sprintf('failed: %s', $url));
    }
}
