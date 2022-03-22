<?php

namespace Henzeb\Jukebox\Tests\Unit\Common;

use GuzzleHttp\Psr7\Uri;
use Henzeb\Jukebox\Jukebox;
use PHPUnit\Framework\TestCase;
use Henzeb\Jukebox\Tests\Fixtures\TestCrawlObserver;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsTrue;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsFalse;
use Henzeb\Jukebox\Collections\JukeboxObserverCollection;

class JukeboxTest extends TestCase
{
    public function testShouldPickNoUrl(): void
    {
        $picked = (new Jukebox())->pick(new Uri('www.google.nl'));

        $this->assertNull($picked);
    }

    public function testShouldPickObserver(): void
    {
        $observer = new TestCrawlObserver();
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsTrue(), $observer);

        $picked = $jukebox->pick(new Uri('www.google.nl'));

        $this->assertTrue($picked === $observer);
    }

    public function testShouldPickSecondObserver(): void
    {
        $observer = new TestCrawlObserver();
        $observer2 = new TestCrawlObserver();
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), $observer);
        $jukebox->add(new ProfileReturnsTrue(), $observer2);

        $picked = $jukebox->pick(new Uri('www.google.nl'));

        $this->assertTrue($picked === $observer2);
    }

    public function testShouldPickObserverCollection(): void
    {
        $observer = new TestCrawlObserver();
        $observer2 = new TestCrawlObserver();
        $collection = new JukeboxObserverCollection($observer, $observer2);
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsTrue(), $collection);

        $picked = $jukebox->pick(new Uri('www.google.nl'));

        $this->assertTrue($picked === $collection);
    }

    public function testshouldPickReturnsFalseWhenNoProfiles(): void
    {
        $jukebox = new Jukebox();

        $this->assertFalse($jukebox->shouldPick(new Uri('www.google.nl')));
    }

    public function testshouldPickReturnsFalse(): void
    {
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), new TestCrawlObserver());

        $this->assertFalse($jukebox->shouldPick(new Uri('www.google.nl')));
    }

    public function testshouldPickReturnsTrue(): void
    {
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsTrue(), new TestCrawlObserver());

        $this->assertTrue($jukebox->shouldPick(new Uri('www.google.nl')));
    }
}
