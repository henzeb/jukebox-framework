<?php

namespace Henzeb\Jukebox\Tests\Unit\Factories;


use Mockery;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Config;
use Henzeb\Jukebox\Queues\ArrayCrawlQueue;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Henzeb\Jukebox\Factories\JukeboxFactory;
use Henzeb\Jukebox\Queues\IlluminateCacheQueue;
use Henzeb\Jukebox\Tests\Fixtures\TestCrawlObserver;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsFalse;

class JukeboxFactoryTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testShouldReturnDefaultInstanceWhenApplication()
    {
        $this->setUpTheTestEnvironment();
        $this->assertInstanceOf(ArrayCrawlQueue::class, (new JukeboxFactory())->queue('test'));
    }

    public function testShouldReturnConfiguredInstanceWhenApplication()
    {
        $this->setUpTheTestEnvironment();
        Config::set('jukebox.queue', IlluminateCacheQueue::class);
        $this->assertInstanceOf(IlluminateCacheQueue::class, (new JukeboxFactory())->queue('test'));
    }

    public function testShouldReturnBasicInstanceWhenNoApplication()
    {
        $this->assertInstanceOf(ArrayCrawlQueue::class, (new JukeboxFactory())->queue('test'));
    }

    public function testShouldReturnSameQueue(): void
    {
        $factory = new JukeboxFactory();
        $this->assertTrue($factory->queue('test') === $factory->queue('test'));
    }

    public function testShouldReturnDifferentQueue(): void
    {
        $factory = new JukeboxFactory();
        $this->assertFalse($factory->queue('test') === $factory->queue('another'));
    }

    public function testCreateShouldGiveNewJukeboxManager(): void
    {
        $factory = new JukeboxFactory();

        $this->assertFalse($factory->create() === $factory->create());
    }

    public function testAddShouldCreateAndAddToJukeboxManager(): void
    {
        $profile = new ProfileReturnsFalse();
        $observer = new TestCrawlObserver();

        $manager = Mockery::mock(JukeboxManager::class);
        $manager->expects('add')->with($profile, $observer);
        $factory = Mockery::mock(JukeboxFactory::class)->makePartial();
        $factory->expects('create')->andReturnUsing(fn() => $manager);

        $factory->add($profile, $observer);
    }

    public function testAddShouldAllowWithoutObserver(): void
    {
        $profile = new ProfileReturnsFalse();

        $manager = Mockery::mock(JukeboxManager::class);
        $manager->expects('add')->with($profile, null);
        $factory = Mockery::mock(JukeboxFactory::class)->makePartial();
        $factory->expects('create')->andReturnUsing(fn() => $manager);

        $factory->add($profile);
    }
}
