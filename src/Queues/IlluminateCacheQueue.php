<?php

namespace Henzeb\Jukebox\Queues;

use Cache;
use Closure;
use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Henzeb\Jukebox\Queues\Contracts\JukeboxQueue;
use Henzeb\Jukebox\Queues\Contracts\JukeboxBaseQueue;

class IlluminateCacheQueue extends JukeboxBaseQueue
{
    public function __construct(
        private readonly string $identifier,
        private ?int            $ttl = null
    )
    {
        parent::__construct($identifier);
    }

    protected function writeUrls(string $key, array $urls): void
    {
        Cache::put(
            $key,
            $urls,
            $this->getTtl()
        );
    }

    protected function writePendingUrls(string $key, array $pendingUrls): void
    {
        Cache::put(
            $key,
            $pendingUrls,
            $this->getTtl()
        );
    }

    protected function getUrls(string $key): array
    {
        return Cache::get($key, []);
    }

    protected function getPendingUrls(string $key): array
    {
        return Cache::get($key, []);
    }

    protected function clearCache(string $key): void
    {
        Cache::delete($key);
    }

    protected function getTtl(): ?int
    {
        return $this->ttl;
    }

    protected function lock(Closure $closure): mixed
    {
        return Cache::lock($this->identifier, 5)
            ->block(
                10,
                function () use ($closure) {
                    $this->refresh();
                    return $closure();
                }
            );
    }

    public function __destruct()
    {
        Cache::lock($this->identifier)->forceRelease();
    }
}
