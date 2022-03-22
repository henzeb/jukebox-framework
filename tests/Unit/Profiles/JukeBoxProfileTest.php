<?php

namespace Henzeb\Jukebox\Tests\Unit\Common\Profiles;


use GuzzleHttp\Psr7\Uri;
use Henzeb\Jukebox\Jukebox;
use PHPUnit\Framework\TestCase;
use Henzeb\Jukebox\Profiles\JukeboxProfile;
use Henzeb\Jukebox\Tests\Fixtures\TestJukebox;
use Henzeb\Jukebox\Tests\Fixtures\TestCrawlObserver;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsTrue;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsFalse;

class JukeBoxProfileTest extends TestCase
{
    public function testShouldReturnFalseWhenNoProfiles(): void
    {
        $jukebox = new Jukebox();

        $this->assertFalse((new JukeboxProfile($jukebox))->shouldCrawl(new Uri('www.google.nl')));
    }

    public function testShouldReturnFalse(): void
    {
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), new TestCrawlObserver());

        $this->assertFalse((new JukeboxProfile($jukebox))->shouldCrawl(new Uri('www.google.nl')));
    }


    public function testShouldReturnTrue(): void
    {
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsTrue(), new TestCrawlObserver());

        $this->assertTrue((new JukeboxProfile($jukebox))->shouldCrawl(new Uri('www.google.nl')));
    }
}
