<?php

namespace Henzeb\Jukebox\Queues;

use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlQueues\CrawlQueue;
use Henzeb\Jukebox\Queues\Contracts\JukeboxQueue;
use Spatie\Crawler\Exceptions\UrlNotFoundByIndex;
use Henzeb\Jukebox\Queues\Contracts\JukeboxBaseQueue;
use Henzeb\Jukebox\Queues\Contracts\JukeboxQueueModel;

class LaravelModelQueue extends JukeboxQueue
{
    private bool $randomly = false;
    private array $pendingBuffer = [];

    public function __construct(
        private JukeboxQueueModel $model,
        private string            $identifier,
        private int               $chunkSize = 5,
        private int               $tries = 3,
    )
    {
    }

    public function has(UriInterface|CrawlUrl $crawlUrl): bool
    {
        return $this->model::has(
            JukeboxCrawlUrl::from($crawlUrl),
            $this->identifier
        );
    }

    public function hasPendingUrls(): bool
    {
        return $this->model::countPendingUrls(
                $this->identifier
            ) > 0;
    }

    public function getProcessedUrlCount(): int
    {
        return $this->model::countProcessedUrls(
            $this->identifier
        );
    }

    public function getPendingUrlCount(): int
    {
        return $this->model::countPendingUrls(
            $this->identifier
        );
    }

    public function add(CrawlUrl $url): JukeboxQueue
    {
        $this->model::add(JukeboxCrawlUrl::from($url), $this->identifier);

        return $this;
    }

    public function getUrlById($id): JukeboxCrawlUrl
    {
        if ($url = $this->pendingBuffer[$id] ?? $this->model::getById($id, $this->identifier)) {
            return $url;
        }

        throw new UrlNotFoundByIndex("Crawl url {$id} not found in queue.");
    }

    public function getPendingUrl(): ?JukeboxCrawlUrl
    {
        if (empty($this->pendingBuffer)) {
            $this->pendingBuffer = $this->model::getPending($this->identifier, $this->chunkSize, $this->randomly);
        }

        if (!empty($this->pendingBuffer)) {
            return array_shift($this->pendingBuffer);
        }

        return null;
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool
    {
        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl): void
    {
        $this->model::markAsProcessed(
            JukeboxCrawlUrl::from($crawlUrl),
            $this->identifier
        );
    }

    public function retry(CrawlUrl|UriInterface $crawlUrl): void
    {
        $this->model::retry(
            JukeboxCrawlUrl::from($crawlUrl),
            $this->identifier,
            $this->tries
        );
    }

    public function clear(): void
    {
        $this->model::truncate();
    }

    public function disableRandom(): void
    {
        $this->randomly = false;
    }

    public function enableRandom(): void
    {
        $this->randomly = true;
    }

    public function remove(CrawlUrl $url): void
    {
        $this->getUrlById($url->getId())
            ->getMeta('model')
            ->delete();
    }

    public function setMaxTries(int $tries): JukeboxQueue
    {
        $this->tries = $tries;
        return $this;
    }
}
