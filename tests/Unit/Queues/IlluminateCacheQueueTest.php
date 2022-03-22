<?php

namespace Henzeb\Jukebox\Tests\Unit\Common\Queues;

use Cache;
use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\CrawlUrl;
use Orchestra\Testbench\TestCase;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Henzeb\Jukebox\Queues\IlluminateCacheQueue;

class IlluminateCacheQueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->tearDown();
    }

    public function testGetPendingShouldReturnNull()
    {
        $queue = new IlluminateCacheQueue('test');
        $this->assertNull($queue->getPendingUrl());
    }

    public function testShouldAddBeCached(): void
    {
        $crawlUrl = JukeboxCrawlUrl::create(new Uri('wwww.google.nl'), new Uri('wwww.google.de'));
        (new IlluminateCacheQueue('test'))->add(
            $crawlUrl
        );

        $this->assertTrue($crawlUrl === (new IlluminateCacheQueue('test'))->getPendingUrl());
    }

    public function testShouldAddBeCachedWithOwnIdentifier(): void
    {
        $crawlUrl = JukeboxCrawlUrl::create(new Uri('wwww.google.nl'), new Uri('wwww.google.de'));
        (new IlluminateCacheQueue('test'))->add(
            $crawlUrl
        );

        $this->assertFalse($crawlUrl === (new IlluminateCacheQueue('test2'))->getPendingUrl());
    }

    public function testShouldMarkAsProcessed()
    {
        $crawlUrl = JukeboxCrawlUrl::create(new Uri('wwww.google.nl'), new Uri('wwww.google.de'));
        $queue = (new IlluminateCacheQueue('test'))->add(
            $crawlUrl
        );
        $this->assertEquals($crawlUrl, $queue->getPendingUrl());
        $queue->markAsProcessed($crawlUrl);
        $this->assertNull($queue->getPendingUrl());
    }

    public function testShouldMarkAsProcessedAndBeCached()
    {
        $crawlUrl = JukeboxCrawlUrl::create(new Uri('wwww.google.nl'), new Uri('wwww.google.de'));
        $queue = (new IlluminateCacheQueue('test'))->add(
            $crawlUrl
        );
        $this->assertEquals($crawlUrl, $queue->getPendingUrl());
        $queue->markAsProcessed($crawlUrl);
        $this->assertNull((new IlluminateCacheQueue('test'))->getPendingUrl());
    }

    public function testCacheShouldUseTtl()
    {
        Cache::shouldReceive('get')->andReturns([]);
        Cache::shouldReceive('put')->twice()->withSomeOfArgs(100);
        Cache::shouldReceive('delete')->twice();

        $crawlUrl = CrawlUrl::create(new Uri('wwww.google.nl'), new Uri('wwww.google.de'));

        (new IlluminateCacheQueue('test', 100))->add(
            $crawlUrl
        );

        $this->expectNotToPerformAssertions();

    }

    public function testCacheShouldUseTtlMarkAsProcessed()
    {
        Cache::shouldReceive('get')->andReturns([]);
        Cache::shouldReceive('put')->times(3)->withSomeOfArgs(100);
        Cache::shouldReceive('delete')->twice();

        $crawlUrl = CrawlUrl::create(new Uri('wwww.google.nl'), new Uri('wwww.google.de'));

        (new IlluminateCacheQueue('test', 100))->add(
            $crawlUrl
        )->markAsProcessed($crawlUrl);

        $this->expectNotToPerformAssertions();

    }

    public function tearDown(): void
    {
        (new IlluminateCacheQueue('test'))->clear();
    }
}
