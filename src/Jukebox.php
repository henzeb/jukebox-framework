<?php

namespace Henzeb\Jukebox;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Henzeb\Jukebox\Observers\JukeboxCrawlObserver;
use Henzeb\Jukebox\Collections\JukeboxObserverCollection;

class Jukebox
{
    private array $profiles = [];
    private array $observers = [];

    public function add(CrawlProfile $profile, JukeboxCrawlObserver|JukeboxObserverCollection|array $observers): void
    {
        $this->profiles[] = $profile;

        if (is_array($observers)) {
            $observers = new JukeboxObserverCollection(...$observers);
        }

        $this->observers[] = $observers;
    }

    public function pick(UriInterface $url): JukeboxCrawlObserver|JukeboxObserverCollection|null
    {
        return $this->observers[$this->match($url)] ?? null;
    }

    public function getObservers(): JukeboxObserverCollection
    {
        return new JukeboxObserverCollection(...$this->observers);
    }

    private function match(UriInterface $url): ?int
    {

        foreach ($this->profiles as $key => $profile) {

            /**
             * @var $profile CrawlProfile
             */
            if ($profile->shouldCrawl($url)) {
                return $key;
            }
        }

        return null;
    }

    public function shouldPick(UriInterface $url): bool
    {
        return null !== $this->match($url);
    }
}
