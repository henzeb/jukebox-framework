<?php

namespace Henzeb\Jukebox\Demo\Profiles;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

class PeopleProfile extends CrawlProfile
{
    public function __construct(private UriInterface $baseUrl)
    {

    }

    public function shouldCrawl(UriInterface $url): bool
    {
        return (
            $this->baseUrl->getHost() === $url->getHost()
            && $url->getPath() !== '/' && !str_contains('copyright', $url->getPath())
        );
    }
}

