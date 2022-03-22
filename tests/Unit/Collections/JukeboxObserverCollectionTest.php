<?php

namespace Henzeb\Jukebox\Tests\Unit\Collections;

use Mockery;
use GuzzleHttp\Psr7\Uri;
use Mockery\MockInterface;
use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Henzeb\Jukebox\Tests\Fixtures\TestCrawlObserver;
use Henzeb\Jukebox\Collections\JukeboxObserverCollection;

class JukeboxObserverCollectionTest extends MockeryTestCase
{
    protected function createObserverMock(): CrawlObserver|MockInterface
    {
        return Mockery::mock(TestCrawlObserver::class)->makePartial();
    }

    public function testWillCrawl(): void
    {
        $url = new Uri('www.google.nl');
        $observer = $this->createObserverMock();
        $observer->expects('willCrawl')->twice()->with($url);

        (new JukeboxObserverCollection($observer, $observer))->willCrawl($url);
    }

    public function testCrawled(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.google.nl');
        $response = Mockery::mock(ResponseInterface::class)->makePartial();

        $observer = $this->createObserverMock();
        $observer->expects('crawled')->twice()->with($url, $response, $foundOn);

        (new JukeboxObserverCollection($observer, $observer))->crawled($url, $response, $foundOn);
    }

    public function testCrawledWithCrawlUrl(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.google.nl');
        $crawlurl = CrawlUrl::create($url, $foundOn);
        $response = Mockery::mock(ResponseInterface::class)->makePartial();

        $observer = $this->createObserverMock();

        $observer->expects('crawled')->twice()->withArgs([$url, $response, $foundOn]);

        (new JukeboxObserverCollection($observer, $observer))->crawled($crawlurl, $response);
    }

    public function testCrawlFailed(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.google.nl');
        $exception = Mockery::mock(RequestException::class)->makePartial();

        $observer = $this->createObserverMock();
        $observer->expects('crawlFailed')->twice()->with($url, $exception, $foundOn);

        (new JukeboxObserverCollection($observer, $observer))->crawlFailed($url, $exception, $foundOn);
    }

    public function testCrawlFailedWithCrawlUrl(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.google.nl');
        $crawlurl = CrawlUrl::create($url, $foundOn);
        $exception = Mockery::mock(RequestException::class)->makePartial();

        $observer = $this->createObserverMock();

        $observer->expects('crawlFailed')->twice()->withArgs([$url, $exception, $foundOn]);

        (new JukeboxObserverCollection($observer, $observer))->crawlFailed($crawlurl, $exception);
    }

    public function testCallsFinishedCrawling(): void
    {
        $observer = $this->createObserverMock();
        $observer->expects('finishedCrawling')->twice();

        (new JukeboxObserverCollection($observer, $observer))->finishedCrawling();
    }

    public function testIterator()
    {
        $observer1 = $this->createObserverMock();
        $observer2 = $this->createObserverMock();
        $iterator = (new JukeboxObserverCollection($observer1, $observer2));
        $actual = [];

        foreach ($iterator as $key => $observer) {
            $actual[$key] = $observer;
        }

        $this->assertEquals([$observer1, $observer2], $actual);
    }

    public function testOfsetGetReturnsNull(): void
    {
        $collection = new JukeboxObserverCollection();

        $this->assertNull($collection[0]);
    }

    public function testOfsetGetReturnsObserver(): void
    {
        $expected = $this->createObserverMock();
        $collection = new JukeboxObserverCollection($expected);

        $this->assertTrue($expected === $collection[0]);
    }

    public function testOffsetSet(): void
    {
        $expected = $this->createObserverMock();
        $collection = new JukeboxObserverCollection();
        $collection[] = $expected;
        $this->assertTrue($expected === $collection[0]);
    }

    public function testOffsetSetWithOffset(): void
    {
        $expected = $this->createObserverMock();
        $collection = new JukeboxObserverCollection();
        $collection[25] = $expected;
        $this->assertTrue($expected === $collection[25]);
    }

    public function testOffsetSetThrowsErrorWithIncorrectValue(): void
    {
        $this->expectError();
        $expected = 'a string';
        $collection = new JukeboxObserverCollection();
        $collection[] = $expected;
    }

    public function testOffsetSetWithOffsetThrowsErrorWithIncorrectValue(): void
    {
        $this->expectError();
        $expected = 'a string';
        $collection = new JukeboxObserverCollection();
        $collection[10] = $expected;
    }

    public function testOffsetNotExists(): void
    {
        $collection = new JukeboxObserverCollection();
        $this->assertFalse(isset($collection[0]));
    }

    public function testOffsetExistsExists(): void
    {
        $collection = new JukeboxObserverCollection($this->createObserverMock());
        $this->assertTrue(isset($collection[0]));
    }

    public function testOffsetUnset(): void
    {
        $collection = new JukeboxObserverCollection($this->createObserverMock());
        unset($collection[0]);
        $this->assertFalse(isset($collection[0]));
    }
}
