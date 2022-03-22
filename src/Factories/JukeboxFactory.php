<?php

namespace Henzeb\Jukebox\Factories;

use Henzeb\Jukebox\Queues\ArrayCrawlQueue;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Henzeb\Jukebox\Queues\Contracts\JukeboxBaseQueue;
use Henzeb\Jukebox\Collections\JukeboxObserverCollection;

class JukeboxFactory
{
    public const JUKEBOX_VERSION = '1.0';
    public const JUKEBOX_NAME = 'Jukebox Crawler Framework';

    private array $queues = [];

    public function queue(string $identifier, ?int $ttl = null): JukeboxBaseQueue
    {
        if (isset($this->queues[$identifier])) {
            return $this->queues[$identifier];
        }

        return $this->queues[$identifier] = $this->getQueue($identifier, $ttl);
    }

    public function create(array $options = []): JukeboxManager
    {
        return new JukeboxManager($options);
    }

    public function add(
        CrawlProfile                                  $profile,
        CrawlObserver|JukeboxObserverCollection|array $observer = null
    ): JukeboxManager
    {
        return $this->create()->add($profile, $observer);
    }

    /**
     * @param string $identifier
     * @param int|null $ttl
     * @return void
     */
    private function getQueue(string $identifier, ?int $ttl, int $tries = 1): JukeboxBaseQueue
    {
        if (function_exists('config') && function_exists('app') && app()->bound('config')) {

            return resolve(
                config('jukebox.queue', ArrayCrawlQueue::class),
                [
                    'identifier' => $identifier,
                    'ttl' => $ttl,
                    'tries'=> $tries
                ]
            );
        }

        return new ArrayCrawlQueue($identifier, $tries);
    }
}
