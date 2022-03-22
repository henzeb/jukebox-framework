<?php

namespace Henzeb\Jukebox\Tests\Unit\Common;

use Mockery;
use GuzzleHttp\Client;
use ReflectionProperty;
use GuzzleHttp\Psr7\Uri;
use Orchestra\Testbench\TestCase;
use Henzeb\Jukebox\Queues\ArrayCrawlQueue;
use Henzeb\Jukebox\Observers\EmptyObserver;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Henzeb\Jukebox\Observers\JukeboxObserver;
use Henzeb\Jukebox\Tests\Fixtures\TestCrawlObserver;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsTrue;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsFalse;

class JukeboxManagerTest extends TestCase
{
    public function testShouldAddToSameJukebox(): void
    {
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock2 = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('willCrawl');
        $mock2->expects('willCrawl')->never();
        $manager = new JukeboxManager();
        $manager = $manager->add(new ProfileReturnsTrue(), $mock);
        $manager2 = $manager->add(new ProfileReturnsFalse(), $mock2);

        $this->assertTrue($manager === $manager2);

        $manager2->observer()->willCrawl(new Uri('www.google.nl'));
    }

    public function testShouldReturnProfileThatReturnsTrue(): void
    {
        $manager = new JukeboxManager();
        $manager->add(new ProfileReturnsTrue(), new TestCrawlObserver());

        $this->assertTrue(
            $manager->profile()->shouldCrawl(new Uri('www.google.nl'))
        );
    }

    public function testShouldCallObserver(): void
    {
        $mock = Mockery::mock(TestCrawlObserver::class);
        $mock->expects('willCrawl');
        $manager = new JukeboxManager();
        $manager->add(new ProfileReturnsTrue(), $mock);

        $manager->observer()->willCrawl(new Uri('www.google.nl'));
    }

    public function testShouldAllowToAddProfileWithoutObserver()
    {
        $phpunit = $this;
        $manager = Mockery::mock(JukeboxManager::class)->makePartial();
        $manager->expects('observer')->andReturnUsing(
            (function () use ($phpunit) {

                $phpunit->assertInstanceOf(
                    EmptyObserver::class,
                    $this->jukebox()->pick(new Uri('www.google.nl'))
                );
                return new JukeboxObserver($this->jukebox());
            })->bindTo($manager, JukeboxManager::class)
        );

        $manager->add(new ProfileReturnsTrue());
        $manager->observer();
    }

    public function testSetCrawlProfileRecreatesJukebox(): void
    {
        $observer = new TestCrawlObserver();
        $profile = new ProfileReturnsTrue();
        $manager = new JukeboxManager();

        $reflection = (new ReflectionProperty($manager, 'jukebox'));

        $manager->setCrawlProfile($profile, $observer);
        $expectedJukebox = $reflection->getValue($manager);
        $actualJukebox = $manager->setCrawlProfile($profile, $observer);

        $this->assertTrue($expectedJukebox !== $actualJukebox);
    }

    public function testSetObserverCallsAdd(): void
    {
        $observer = new TestCrawlObserver();
        $profile = new ProfileReturnsTrue();
        $manager = Mockery::mock(JukeboxManager::class)->makePartial();
        $manager->expects('add')->with($profile, $observer);

        $manager->setCrawlProfile($profile, $observer);

    }

    public function testSetCrawlProfileCallsAdd(): void
    {
        $observer = new TestCrawlObserver();
        $profile = new ProfileReturnsTrue();
        $manager = Mockery::mock(JukeboxManager::class)->makePartial();
        $manager->expects('add')->with($profile, $observer);

        $manager->setCrawlObserver($observer, $profile);
    }

    public function testAddCrawlProfileCallsAddOnExistingJukebox(): void
    {
        $observer = new TestCrawlObserver();
        $profile = new ProfileReturnsTrue();
        $manager = new JukeboxManager();

        $reflection = (new ReflectionProperty($manager, 'jukebox'));

        $manager->addCrawlObserver($observer, $profile);
        $expectedJukebox = $reflection->getValue($manager);
        $manager->addCrawlObserver($observer, $profile);
        $actualJukebox = $reflection->getValue($manager);

        $this->assertTrue($expectedJukebox === $actualJukebox);
    }

    public function testSetObserverRecreatesJukebox(): void
    {
        $observer = new TestCrawlObserver();
        $profile = new ProfileReturnsTrue();
        $manager = new JukeboxManager();

        $reflection = (new ReflectionProperty($manager, 'jukebox'));

        $manager->setCrawlProfile($profile, $observer);
        $expectedJukebox = $reflection->getValue($manager);
        $actualJukebox = $manager->setCrawlObserver($observer, $profile);

        $this->assertTrue($expectedJukebox !== $actualJukebox);

    }

    public function testSetCrawlQueue(): void
    {
        $expected = new ArrayCrawlQueue('test');
        $manager = new JukeboxManager();

        $manager->setCrawlQueue($expected);

        $this->assertTrue($expected === $manager->getCrawlQueue());
    }

    public function testMagicCallThrowsBadMethodCallException(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new JukeboxManager())->thisMethodDoesNotAndWillNotEverExist();
    }

    public function testMagicCallReturnsManagerWhenMethodReturnsCrawler(): void
    {
        $expected = new JukeboxManager();
        $actual = $expected->setConcurrency(1);

        $this->assertTrue($expected === $actual);
    }

    public function testMagicCallPassesAlongParameters(): void
    {
        $manager = new JukeboxManager();
        $initialLimit = $manager->getTotalCrawlLimit();
        $manager->setTotalCrawlLimit($initialLimit + 100);
        $actual = $manager->getTotalCrawlLimit();

        $this->assertEquals($initialLimit + 100, $actual);
    }

    public function testGuzzleClientIsMockable(): void
    {
        app()->bind(Client::class, function ($app, array $parameters) {
            $this->assertEquals(
                [
                    "timeout" => 30,
                    "cookies" => true,
                    "connect_timeout" => 10,
                    "allow_redirects" => false,
                    "headers" => [
                        "User-Agent" => "*",
                    ],
                    'option' => 'value',
                ],
                $parameters['config']
            );
            return new Client($parameters['config']);
        });

        $manager = new JukeboxManager(
            [
                'option' => 'value'
            ]
        );
        $manager->startCrawling();
    }
}
