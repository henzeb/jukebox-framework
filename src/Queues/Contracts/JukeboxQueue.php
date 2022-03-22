<?php

namespace Henzeb\Jukebox\Queues\Contracts;

use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlQueues\CrawlQueue;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;

abstract class JukeboxQueue implements CrawlQueue
{
    abstract public function add(CrawlUrl $url): JukeboxQueue;

    abstract public function getUrlById($id): JukeboxCrawlUrl;

    abstract public function remove(CrawlUrl $url): void;

    abstract public function getPendingUrl(): ?JukeboxCrawlUrl;

    abstract public function hasAlreadyBeenProcessed(CrawlUrl $url): bool;

    abstract public function markAsProcessed(CrawlUrl $crawlUrl): void;

    abstract public function setMaxTries(int $tries): self;

    abstract public function retry(CrawlUrl|UriInterface $crawlUrl): void;

    abstract public function clear(): void;

    abstract public function disableRandom(): void;

    abstract public function enableRandom(): void;

    abstract public function getPendingUrlCount(): int;
}
