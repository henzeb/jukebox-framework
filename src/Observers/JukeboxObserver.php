<?php

namespace Henzeb\Jukebox\Observers;

use Henzeb\Jukebox\Jukebox;
use Psr\Http\Message\UriInterface;
use Henzeb\Console\Facades\Console;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Symfony\Component\Console\Output\OutputInterface;
use Henzeb\Jukebox\Collections\JukeboxObserverCollection;

class JukeboxObserver extends CrawlObserver
{
    /**
     * @var JukeboxCrawlUrl[]
     */
    private array $crawlUrlsInProgress = [];

    public function __construct(
        private Jukebox $jukebox,
        private JukeboxManager $manager
    )
    {
    }

    public function markForProcessing(JukeboxCrawlUrl $crawlUrl): void
    {
        $this->crawlUrlsInProgress[(string)$crawlUrl->url] = $crawlUrl;
    }

    private function removeCrawlUrl(UriInterface $uri): void
    {
        if (isset($this->crawlUrlsInProgress[(string)$uri])) {
            unset($this->crawlUrlsInProgress[(string)$uri]);
        }
    }

    public function willCrawl(UriInterface $url): void
    {
        Console::info(
            sprintf('Will crawl: %s', $url),
            OutputInterface::VERBOSITY_DEBUG
        );
        $this->pickCollection($url)?->willCrawl($url);
    }

    private function pickCollection(UriInterface $url): JukeboxObserverCollection|JukeboxCrawlObserver|null
    {
        return $this->jukebox->pick($url);
    }

    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {

        Console::info(
            sprintf('Crawled: %s, with responsecode %d', $url, $response->getStatusCode()),
            OutputInterface::VERBOSITY_VERY_VERBOSE
        );
        $this->pickCollection($url)?->crawled($this->getPendingCrawlUrl($url), $response, $foundOnUrl);

        $this->removeCrawlUrl($url);
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null): void
    {
        Console::error(
            sprintf('Crawled: %s, with message %s', $url, $requestException->getMessage()),
            OutputInterface::VERBOSITY_VERY_VERBOSE
        );

        $pending = $this->getPendingCrawlUrl($url);

        $this->pickCollection($url)?->crawlFailed($pending, $requestException);

        if($requestException->getCode() !== 404) {
            $this->manager->getCrawlQueue()->retry($pending);
        }

        $this->removeCrawlUrl($url);
    }

    public function finishedCrawling(): void
    {
        Console::info(
            'Finished crawling',
            OutputInterface::VERBOSITY_DEBUG
        );
        $this->jukebox->getObservers()->finishedCrawling();
    }

    private function getPendingCrawlUrl(UriInterface $url): JukeboxCrawlUrl
    {
        return $this->crawlUrlsInProgress[(string)$url];
    }
}
