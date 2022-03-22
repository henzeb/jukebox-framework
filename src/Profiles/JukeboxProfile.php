<?php

namespace Henzeb\Jukebox\Profiles;

use Henzeb\Jukebox\Jukebox;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

class JukeboxProfile extends CrawlProfile
{
    public function __construct(private Jukebox $jukebox)
    {
    }

    public function shouldCrawl(UriInterface $url): bool
    {
       return $this->jukebox->shouldPick($url);
    }
}
