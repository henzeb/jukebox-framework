<?php

namespace Henzeb\Jukebox\Queues;

use Henzeb\Jukebox\Queues\Contracts\JukeboxBaseQueue;

class ArrayCrawlQueue extends JukeboxBaseQueue
{
    protected function getUrls(string $key): array
    {
        return $this->urls;
    }

    protected function getPendingUrls(string $key): array
    {
        return $this->pendingUrls;
    }

    protected function writeUrls(string $key, array $urls): void
    {
        //
    }

    protected function writePendingUrls(string $key, array $pendingUrls): void
    {
        //
    }

    protected function clearCache(string $key): void
    {
        //
    }
}
