<?php

namespace Henzeb\Jukebox\Crawlers;


use Closure;
use Generator;
use Tree\Node\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\UriInterface;
use Henzeb\Jukebox\Proxies\NoProxy;
use Psr\Http\Message\RequestInterface;
use Spatie\Crawler\CrawlQueues\CrawlQueue;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Henzeb\Jukebox\Queues\ArrayCrawlQueue;
use Henzeb\Jukebox\Observers\JukeboxObserver;
use Henzeb\Jukebox\Queues\Contracts\JukeboxQueue;
use Henzeb\Jukebox\Queues\Contracts\JukeboxBaseQueue;
use Henzeb\Jukebox\Proxies\Contracts\RequestProxy;

class JukeboxCrawler extends Crawler
{
    protected JukeboxBaseQueue|CrawlQueue $crawlQueue;
    protected RequestProxy $proxy;
    private Closure $randomDelayGenerator;

    private int|null|float $maxDelayBetweenRequests = null;
    private int $chunkSize = 10;
    private ?Closure $onCrawlStart = null;
    private ?Closure $onGetCrawlRequests = null;
    private ?Closure $onEachChunk = null;


    public function __construct(Client $client, int $concurrency = 10)
    {
        $this->randomDelayGenerator = fn(int $min, int $max) => mt_rand($min, $max);

        $this->baseUrl = new Uri();

        $this->proxy = new NoProxy();

        parent::__construct($client, $concurrency);

        $this->setCrawlQueue(new ArrayCrawlQueue('default'));
    }

    public static function defaultClientOptions(): array
    {
        return self::$defaultClientOptions;
    }

    public function setProxy(RequestProxy $proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function setOnCrawlStart(callable $onCrawlStart): self
    {
        $this->onCrawlStart = $onCrawlStart;

        return $this;
    }

    public function setOnGetCrawlRequests(callable $onGetCrawlRequests): self
    {
        $this->onGetCrawlRequests = $onGetCrawlRequests;

        return $this;
    }

    public function setOnEachChunk(callable $onEachChunk): self
    {
        $this->onEachChunk = $onEachChunk;

        return $this;
    }

    public function setChunkSize(int $chunkSize): self
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    public function getCookieJar(): CookieJar
    {
        return Closure::bind(function () {
            /**
             * @var $this Client
             */
            return $this->config['cookies'] ?: new CookieJar();
        }, $this->client, Client::class)();
    }

    public function setDelayBetweenRequests(int $delayInSeconds, int $maxDelayInSeconds = null): self
    {
        $this->delayBetweenRequests = ($delayInSeconds * 100);
        $this->maxDelayBetweenRequests = $maxDelayInSeconds ? ($maxDelayInSeconds * 100) : null;

        return $this;
    }

    public function getDelayBetweenRequests(): int
    {
        if (null !== $this->maxDelayBetweenRequests) {
            return ($this->randomDelayGenerator)(
                    $this->delayBetweenRequests,
                    $this->maxDelayBetweenRequests
                ) * 10;
        }

        return parent::getDelayBetweenRequests() * 10;
    }

    public function setCrawlQueue(CrawlQueue $crawlQueue): self
    {
        /**
         * @var $crawlQueue JukeboxQueue
         */
        return $this->setJukeboxCrawlQueue($crawlQueue);
    }

    public function getCrawlQueue(): JukeboxQueue
    {
        return $this->crawlQueue;
    }

    public function disableRandom(): void
    {
        $this->getCrawlQueue()->disableRandom();
    }

    public function enableRandom(): void
    {
        $this->getCrawlQueue()->enableRandom();
    }

    private function setJukeboxCrawlQueue(JukeboxQueue $jukeboxQueue): self
    {
        parent::setCrawlQueue($jukeboxQueue);
        return $this;
    }

    public function addToCrawlQueue(
        CrawlUrl|RequestInterface|UriInterface $crawlUrl
    ): self
    {
        $crawlUrl = JukeboxCrawlUrl::from($crawlUrl);

        if (!isset($this->baseUrl)) {
            $this->baseUrl = $crawlUrl->url;

            $this->depthTree = new Node((string)$this->baseUrl);
        }

        if (!$this->getCrawlProfile()->shouldCrawl($crawlUrl->url)) {
            return $this;
        }

        if ($this->getCrawlQueue()->has($crawlUrl)) {

            return $this;
        }

        $this->getCrawlQueue()->add($crawlUrl);

        return $this;
    }

    protected function startCrawlingQueue(): void
    {
        if ($this->onCrawlStart) {
            ($this->onCrawlStart)($this);
        }

        parent::startCrawlingQueue();
    }

    protected function getCrawlRequests(): Generator
    {
        /**
         * allows for more flexibility, like adding post/head requests and managing cookies.
         */

        if ($this->onGetCrawlRequests) {
            ($this->onGetCrawlRequests)($this);
        }

        $chunkCount = 0;

        foreach (parent::getCrawlRequests() as $id => $request) {
            $crawlUrl = $this->getCrawlQueue()->getUrlById($id);
            $chunkCount++;
            if ($crawlUrl->clearCookies()) {
                $this->getCookieJar()->clear();
            }
            $this->markForProcessing($crawlUrl);
            yield $id => $this->throughProxy(
                $crawlUrl
            );

            if (($chunkCount % $this->chunkSize === 0) && $this->onEachChunk) {
                ($this->onEachChunk)($this, $crawlUrl);
                $chunkCount = 0;
            }
        }
    }

    public function startCrawling(CrawlUrl|RequestInterface|UriInterface|string|null $baseUrl = null): void
    {
        if ($baseUrl instanceof UriInterface || is_string($baseUrl)) {
            parent::startCrawling($baseUrl);
            return;
        }

        if (null !== $baseUrl) {
            $this->addToCrawlQueue($baseUrl);
        }

        $this->startCrawlingQueue();

        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->finishedCrawling();
        }

    }

    private function throughProxy(JukeboxCrawlUrl $crawlUrl): RequestInterface
    {
        if ($crawlUrl->useProxy()) {
            return $this->proxy->wrap($crawlUrl->getRequest());
        }

        return $crawlUrl->getRequest();
    }

    private function markForProcessing(JukeboxCrawlUrl $crawlUrl): void
    {
        foreach($this->getCrawlObservers() as $observer) {
            if($observer instanceof JukeboxObserver) {
                $observer->markForProcessing($crawlUrl);
            }
        }
    }
}
