<?php

namespace Henzeb\Jukebox\Console\Commands\Contracts;


use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\CrawlUrl;
use Illuminate\Console\Command;
use Psr\Http\Message\UriInterface;
use Henzeb\Jukebox\Facades\Jukebox;
use Henzeb\Console\Facades\Console;
use Psr\Http\Message\RequestInterface;
use Henzeb\Jukebox\Queues\ArrayCrawlQueue;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Symfony\Component\Console\Input\InputOption;
use Henzeb\Jukebox\Queues\Contracts\JukeboxQueue;

abstract class JukeboxCommand extends Command
{
    private ?int $limit = null;
    private ?int $delay = 1000;
    private ?int $maxDelay = null;
    private ?int $totalLimit = null;
    private ?JukeboxQueue $queue = null;
    private UriInterface|RequestInterface|null $url = null;

    public function __construct()
    {
        $this->signature = $this->signature();
        $this->description = $this->description();

        parent::__construct();

        $this->addOption('clear-cache', 'c', InputOption::VALUE_NONE, 'Clears the cache');
        $this->addOption('clear-and-exit', 'C', InputOption::VALUE_NONE, 'Clears the cache and exits');
        $this->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'Sets the delay');
        $this->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Sets the limit');
        $this->addOption('retries', 'rt', InputOption::VALUE_REQUIRED, 'Sets the count of retries', 3);
        $this->addOption('total-limit', 't', InputOption::VALUE_REQUIRED, 'Sets the total limit');
        $this->addOption('random', 'r', InputOption::VALUE_NEGATABLE, 'Enable or disable randomly picking from the queue');
        $this->addOption('info', 'i', InputOption::VALUE_NONE, 'show some info about the queue');


        $this->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'crawl from specific URL');
    }

    abstract protected function signature(): string;

    abstract protected function description(): string;

    abstract protected function url(): UriInterface|RequestInterface|null;

    abstract protected function jukebox(): JukeboxManager;

    protected function whenClearingCache(): void
    {
        //
    }

    protected function whenDoneCrawling(): void
    {
        //
    }

    protected function queue(): JukeboxQueue
    {
        $identifier = $this::class;

        if($url = $this->getUrl()) {
            $identifier = (string)JukeboxCrawlUrl::from($url)->url;
        }

        return Jukebox::queue($identifier);
    }

    final protected function getQueue(): JukeboxQueue
    {
        if ($this->queue) {
            return $this->queue;
        }

        return $this->queue = $this->queue();
    }

    private function limit(): ?int
    {
        return $this->limit;
    }

    final protected function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    private function totalLimit(): ?int
    {
        return $this->totalLimit;
    }

    final protected function setTotalLimit(?int $totalLimit): void
    {
        $this->totalLimit = $totalLimit;
    }

    private function delay(): int
    {
        return $this->delay;
    }

    private function maxDelay(): ?int
    {
        return $this->maxDelay;
    }

    final protected function setDelay(?int $delay, ?int $maxDelay = null): void
    {
        $this->delay = $delay;
        $this->maxDelay = $maxDelay;
    }

    final public function handle()
    {
        $crawler = $this->jukebox();

        $this->configureOptions();

        if ($this->option('clear-and-exit')) {
            return 0;
        }

        if(!$this->option('info')) {
            $this->crawl($crawler);

            $this->executeWhenDone();
        }

        Console::info(sprintf('pending: %d, total processed: %d', $this->getQueue()->getPendingUrlCount(), $this->getQueue()->getProcessedUrlCount()));

        return 0;
    }

    private function configureOptions(): void
    {
        if ($this->option('clear-cache') || $this->option('clear-and-exit')) {
            $this->getQueue()->clear();
            $this->whenClearingCache();
            Console::info('Cache cleared!');
        }

        if (($limit = $this->option('limit')) !== null) {
            $this->setLimit($limit);
        }

        if (($tries = $this->option('retries')) !== null) {
            $this->getQueue()->setMaxTries($tries);
        }

        if (($limit = $this->option('total-limit')) !== null) {
            $this->setTotalLimit($limit);
        }

        if (($delay = $this->option('delay')) !== null) {
            $this->setDelay(...explode(',', $delay));
        }

        if ($this->option('url')) {

            $this->queueSingleUrl($this->option('url'));
        }
    }

    private function crawl(JukeboxManager $crawler): void
    {
        $this->configureCrawler($crawler);

        $url = $this->getUrl();

        if ($url instanceof RequestInterface) {
            $crawler->addToCrawlQueue($url);
            $url = null;
        }

        $crawler->startCrawling($url);
    }

    private function configureCrawler(JukeboxManager $crawler)
    {
        if ($this->limit() >= 0 && null !== $this->limit()) {
            $crawler->setCurrentCrawlLimit($this->limit());
        }

        if ($this->totalLimit() >= 0 && null !== $this->totalLimit()) {
            $crawler->setTotalCrawlLimit($this->totalLimit());
        }

        if ($this->delay() >= 0 && null !== $this->delay()) {
            $crawler->setDelayBetweenRequests($this->delay(), $this->maxDelay());
        }

        if ($this->option('random')) {
            $crawler->enableRandom();
        }

        if (!$this->option('random') && null !== $this->option('random')) {
            $crawler->disableRandom();
        }

        $crawler->setCrawlQueue(
            $this->getQueue()
        );
    }

    private function executeWhenDone(): void
    {
        if (!$this->getQueue()->hasPendingUrls()) {
            $this->whenDoneCrawling();
        }
    }

    private function queueSingleUrl(string $url): void
    {
        $this->url = new Uri($url);
        $this->queue = new ArrayCrawlQueue('single-queue');
        $this->queue->add(CrawlUrl::create($this->url));
    }

    private function getUrl(): UriInterface|RequestInterface|null
    {
        if (isset($this->url)) {
            return $this->url;
        }

        return $this->url = $this->url();
    }
}
