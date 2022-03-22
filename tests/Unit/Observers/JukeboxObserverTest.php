<?php

namespace Henzeb\Jukebox\Tests\Unit\Common\Observers;


use Mockery;
use GuzzleHttp\Psr7\Uri;
use Henzeb\Jukebox\Jukebox;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Henzeb\Jukebox\Observers\JukeboxObserver;
use Henzeb\Jukebox\Tests\Fixtures\TestCrawlObserver;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsTrue;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsFalse;
use Henzeb\Jukebox\Collections\JukeboxObserverCollection;

class JukeboxObserverTest extends MockeryTestCase
{
    public function testShouldNotThrowErrorsWhenNoObserverReturned(): void
    {
        $jukebox = new Jukebox();
        $observer = new JukeboxObserver($jukebox);
        $observer->willCrawl(new Uri('www.google.nl'));
        $this->expectNotToPerformAssertions();
    }

    public function testShouldCallWillCrawl(): void
    {
        $url = new Uri('www.google.nl');
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('willCrawl')->never();

        $mock2 = Mockery::mock(TestCrawlObserver::class);
        $mock2->expects('willCrawl')->with($url);

        $jukebox = new Jukebox();
        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), $mock);
        $jukebox->add(new ProfileReturnsTrue(), $mock2);
        $observer = new JukeboxObserver($jukebox);
        $observer->willCrawl($url);
    }

    public function testShouldCallWillCrawlOnCollection(): void
    {
        $url = new Uri('www.google.nl');
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('willCrawl')->with($url);

        $mock2 = Mockery::mock(TestCrawlObserver::class);
        $mock2->expects('willCrawl')->with($url);

        $jukebox = new Jukebox();

        $jukebox->add(new ProfileReturnsFalse(), new TestCrawlObserver());
        $jukebox->add(new ProfileReturnsTrue(), [$mock, $mock2]);

        $observer = new JukeboxObserver($jukebox);
        $observer->willCrawl($url);
    }

    public function testShouldCallCrawled(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.tweakers.net');
        $response = Mockery::mock(ResponseInterface::class)->makePartial();
        $response->expects('getStatusCode')->andReturn(200);
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('crawled')->never();

        $mock2 = Mockery::mock(TestCrawlObserver::class);
        $mock2->expects('crawled')->with($url, $response, $foundOn);

        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), $mock);
        $jukebox->add(new ProfileReturnsTrue(), $mock2);
        $observer = new JukeboxObserver($jukebox);
        $observer->crawled($url, $response, $foundOn);
    }

    public function testShouldCallCrawledOnCollection(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.tweakers.net');
        $response = Mockery::mock(ResponseInterface::class)->makePartial();
        $response->expects('getStatusCode')->andReturn(200);
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('crawled')->with($url, $response, $foundOn);

        $mock2 = Mockery::mock(TestCrawlObserver::class);
        $mock2->expects('crawled')->with($url, $response, $foundOn);

        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), new TestCrawlObserver());
        $jukebox->add(new ProfileReturnsTrue(), new JukeboxObserverCollection($mock, $mock2->makePartial()));

        $observer = new JukeboxObserver($jukebox);
        $observer->crawled($url, $response, $foundOn);
    }

    public function testShouldCallCrawlFailed(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.tweakers.net');
        $exception = Mockery::mock(RequestException::class)->makePartial();
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('crawlFailed')->never();

        $mock2 = Mockery::mock(TestCrawlObserver::class);
        $mock2->expects('crawlFailed')->with($url, $exception, $foundOn);

        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), $mock);
        $jukebox->add(new ProfileReturnsTrue(), $mock2);
        $observer = new JukeboxObserver($jukebox);
        $observer->crawlFailed($url, $exception, $foundOn);
    }

    public function testShouldCallCrawlFailedOnCollection(): void
    {
        $url = new Uri('www.google.nl');
        $foundOn = new Uri('www.tweakers.net');
        $exception = Mockery::mock(RequestException::class)->makePartial();
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('crawlFailed')->with($url, $exception, $foundOn);

        $mock2 = Mockery::mock(TestCrawlObserver::class);
        $mock2->expects('crawlFailed')->with($url, $exception, $foundOn);

        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), new TestCrawlObserver());
        $jukebox->add(new ProfileReturnsTrue(), [$mock, $mock2]);
        $observer = new JukeboxObserver($jukebox);
        $observer->crawlFailed($url, $exception, $foundOn);
    }
}
