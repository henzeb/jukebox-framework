<?php
namespace Henzeb\Jukebox\Tests\Fixtures;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

class ProfileReturnsTrue extends CrawlProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        return true;
    }
}
