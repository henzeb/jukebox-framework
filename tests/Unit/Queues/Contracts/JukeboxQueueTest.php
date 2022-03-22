<?php

use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\CrawlUrl;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Henzeb\Jukebox\Queues\Contracts\JukeboxBaseQueue;
use Spatie\Crawler\Exceptions\UrlNotFoundByIndex;

class JukeboxQueueTest extends MockeryTestCase
{
    public function testShouldGetUrlById(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $queue->expects('writeToCache');

        $crawlUrl = JukeboxCrawlUrl::create(new Uri('www.google.nl'));
        $queue->add($crawlUrl);

        $this->assertTrue($crawlUrl === $queue->getUrlById($crawlUrl->getId()));
    }

    public function testShouldThrowUrlNotFoundException(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->expectException(UrlNotFoundByIndex::class);

        $queue->getUrlById('not existing');
    }

    public function testHasShouldReturnTrue(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $queue->expects('writeToCache');

        $crawlUrl = JukeboxCrawlUrl::create(new Uri('www.google.nl'));
        $queue->add($crawlUrl);

        $this->assertTrue($queue->has(new Uri('www.google.nl')));
    }

    public function testHasShouldReturnTrueWithCrawlUrl(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $queue->expects('writeToCache');

        $crawlUrl = JukeboxCrawlUrl::create(new Uri('www.google.nl'));
        $queue->add($crawlUrl);

        $this->assertTrue($queue->has(JukeboxCrawlUrl::create(new Uri('www.google.nl'))));
    }

    public function testHasShouldReturnFalse(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->assertFalse($queue->has(new Uri('www.google.nl')));
    }

    public function testHasShouldReturnFalseWithCrawlUrl(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->assertFalse($queue->has(JukeboxCrawlUrl::create(new Uri('www.google.nl'))));
    }

    public function testHasAlreadyBeenProcessedReturnsFalse(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->assertFalse($queue->hasAlreadyBeenProcessed(JukeboxCrawlUrl::create(new Uri('www.google.nl'))));
    }

    public function testHasAlreadyBeenProcessedReturnsFalseWithCrawlQueue(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->assertFalse($queue->hasAlreadyBeenProcessed(JukeboxCrawlUrl::create(new Uri('www.google.nl'))));
    }

    public function testHasAlreadyBeenProcessedReturnsFalseWhenPending(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $queue->expects('writeToCache');
        $queue->add(JukeboxCrawlUrl::create(new Uri('www.google.nl')));

        $this->assertFalse($queue->hasAlreadyBeenProcessed(JukeboxCrawlUrl::create(new Uri('www.google.nl'))));
    }

    public function testHasAlreadyBeenProcessedReturnsTrueWhenProcessed(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $queue->expects('writeToCache');
        $queue->expects('writePendingUrls');

        $queue->add(JukeboxCrawlUrl::create(new Uri('www.google.nl')));
        $queue->markAsProcessed(JukeboxCrawlUrl::create(new Uri('www.google.nl')));

        $this->assertTrue($queue->hasAlreadyBeenProcessed(JukeboxCrawlUrl::create(new Uri('www.google.nl'))));
    }

    public function testShouldGetPendingUrlsInOrder(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $queue->expects('writePendingUrls')->times(1);

        $expectedCrawlUrl1 = JukeboxCrawlUrl::create(new Uri('https://www.google.nl'));
        $expectedCrawlUrl2 = JukeboxCrawlUrl::create(new Uri('https://www.google.de'));

        $queue->expects('getUrls')->once()->andReturn([
            '2295add3622aed66c77daf15b1b99ffc' => $expectedCrawlUrl1,
            '58c0b1dca71498aaad4326384e1fcdcc' => $expectedCrawlUrl2
        ]);
        $queue->expects('getPendingUrls')->once()->andReturn(
            [
                '2295add3622aed66c77daf15b1b99ffc' => $expectedCrawlUrl1,
                '58c0b1dca71498aaad4326384e1fcdcc' => $expectedCrawlUrl2
            ]
        );
        $queue->__construct('test');
        $queue->disableRandom();

        $this->assertTrue($expectedCrawlUrl1 === $queue->getPendingUrl(), 'first');
        $queue->markAsProcessed($expectedCrawlUrl1);
        $this->assertTrue($expectedCrawlUrl2 === $queue->getPendingUrl(), 'second');
    }

    public function testShouldGetPendingUrlsInRandom(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $expectedCrawlUrl = JukeboxCrawlUrl::create(new Uri('https://www.google.de'));

        $queue->expects('getUrls')->once()->andReturn([
            'c07a93b7824d2220804b4e8fe1f277b0' => null,
            '58c0b1dca71498aaad4326384e1fcdcc' => $expectedCrawlUrl
        ]);
        $queue->expects('getPendingUrls')->once()->andReturn(
            [
                'c07a93b7824d2220804b4e8fe1f277b0' => null,
                '58c0b1dca71498aaad4326384e1fcdcc' => $expectedCrawlUrl
            ]
        );

        $queue->__construct('test');
        $queue->disableRandom();
        $queue->enableRandom();

        (function () {
            $this->randomGenerator = fn() => '58c0b1dca71498aaad4326384e1fcdcc';
        })->bindTo($queue, JukeboxBaseQueue::class)();

        $this->assertTrue($expectedCrawlUrl === $queue->getPendingUrl());
    }

    public function testShouldRequeueUrl(): void
    {
        $url = new Uri('www.google.nl');
        $crawlUrl = CrawlUrl::create($url, id: '1');
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $queue->expects('getUrls')->andReturn([]);
        $queue->expects('getPendingUrls')->andReturn([]);
        $queue->expects('writeUrls')->zeroOrMoreTimes();
        $queue->expects('writePendingUrls')->zeroOrMoreTimes();

        $queue->__construct('test');
        $queue->add($crawlUrl);
        $crawlUrl2 = clone $crawlUrl;
        $crawlUrl2->setId('2');
        $queue->add($crawlUrl2);
        $this->assertEquals(2, $queue->getPendingUrlCount());
        $queue->markAsProcessed($crawlUrl);
        $this->assertEquals(1, $queue->getProcessedUrlCount());
        $this->assertEquals(1, $queue->getPendingUrlCount());

        $queue->markAsProcessed($crawlUrl2);
        $this->assertEquals(2, $queue->getProcessedUrlCount());
        $this->assertEquals(0, $queue->getPendingUrlCount());
    }

    public function testShouldNotRequeueUrl(): void
    {
        $url = new Uri('www.google.nl');
        $crawlUrl = CrawlUrl::create($url);
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $queue->expects('getUrls')->andReturn([]);
        $queue->expects('getPendingUrls')->andReturn([]);
        $queue->expects('writeUrls')->times(1);
        $queue->expects('writePendingUrls')->times(3);

        $queue->__construct('test');
        $queue->add($crawlUrl);

        $queue->add($crawlUrl);
        $this->assertEquals(1, $queue->getPendingUrlCount());
        $queue->markAsProcessed($crawlUrl);
        $this->assertEquals(1, $queue->getProcessedUrlCount());
        $this->assertEquals(0, $queue->getPendingUrlCount());

        $queue->markAsProcessed($crawlUrl);
        $this->assertEquals(1, $queue->getProcessedUrlCount());
        $this->assertEquals(0, $queue->getPendingUrlCount());
    }

    public function testShouldRequeueInOrderOfAppearance(): void
    {
        $firstCrawlUrl = CrawlUrl::create(new Uri('www.google.nl'));
        $secondCrawlUrl = CrawlUrl::create(new Uri('www.tweakers.net'));
        $thirdCrawlUrl = CrawlUrl::create(new Uri('www.google.nl'), id: 'random');

        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $queue->expects('getUrls')->andReturn([]);
        $queue->expects('getPendingUrls')->andReturn([]);
        $queue->expects('writeUrls')->zeroOrMoreTimes();
        $queue->expects('writePendingUrls')->zeroOrMoreTimes();
        $queue->__construct('test');
        $queue->disableRandom();
        $queue->add($firstCrawlUrl);
        $queue->add($secondCrawlUrl);
        $queue->add($thirdCrawlUrl);

        $this->assertEquals('www.google.nl', (string)$queue->getPendingUrl()->url, 'first');
        $this->assertEquals('14671a199daf281666bf15a07240e14b', (string)$queue->getPendingUrl()->getId(), 'first id');
        $queue->markAsprocessed($firstCrawlUrl);
        $this->assertEquals('www.tweakers.net', (string)$queue->getPendingUrl()->url, 'second');
        $queue->markAsprocessed($secondCrawlUrl);
        $this->assertEquals('www.google.nl', (string)$queue->getPendingUrl()->url, 'third');
        $this->assertEquals('random', (string)$queue->getPendingUrl()->getId(), 'third id');


    }
}
