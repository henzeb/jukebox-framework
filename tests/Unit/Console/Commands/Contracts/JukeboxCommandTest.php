<?php

namespace Henzeb\Jukebox\Tests\Unit\Console\Commands\Contracts;

use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use Henzeb\Jukebox\Facades\Jukebox;
use GuzzleHttp\Handler\MockHandler;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Henzeb\Jukebox\Queues\Contracts\JukeboxBaseQueue;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Henzeb\Jukebox\Console\Commands\Contracts\JukeboxCommand;
use Henzeb\Jukebox\Tests\Fixtures\Commands\JukeboxTestCommand;
use Henzeb\Jukebox\Tests\Fixtures\Commands\CommandServiceProvider;

class JukeboxCommandTest extends TestCase
{
    private Mockery\MockInterface|JukeboxManager|null $manager = null;

    protected function getPackageProviders($app)
    {
        return [CommandServiceProvider::class, ConsoleServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = Mockery::mock(JukeboxManager::class)
            ->makePartial();

        $this->command()->expects('jukebox')
            ->once()->andReturns($this->manager);

    }

    public function command(): JukeboxTestCommand|Mockery\MockInterface|Mockery\LegacyMockInterface
    {
        return $this->app->get(JukeboxCommand::class);
    }

    public function testShouldRunDefaultProperly(): void
    {
        $this->manager->expects('setDelayBetweenRequests')->with(1000, null);
        $this->manager->expects('setCurrentCrawlLimit')->never();
        $this->manager->expects('setTotalCrawlLimit')->never();

        $this->manager->shouldReceive('startCrawling')->with($this->command()->url());

        $this->command()->shouldReceive('whenDone');
        $this->command()->expects('queue')->passthru();
        $this->command()->shouldReceive('url')->passthru();

        Jukebox::partialMock()->shouldReceive('queue')->passthru();
        $this->artisan('jukebox:test');
    }

    public function testShouldRunDefaultProperlyWithRequest(): void
    {
        $this->manager->expects('setDelayBetweenRequests')->with(1000, null);
        $this->manager->expects('setCurrentCrawlLimit')->never();
        $this->manager->expects('setTotalCrawlLimit')->never();

        $this->manager->shouldReceive('startCrawling')->with(null);

        $this->command()->shouldReceive('whenDone');
        $this->command()->expects('url')->andReturn(new Request('POST', 'www.google.nl'));
        $this->command()->expects('queue')->passthru();
        $this->command()->shouldReceive('url')->passthru();

        Jukebox::partialMock()->shouldReceive('queue')->passthru();
        $this->artisan('jukebox:test');
    }

    public function testShouldNotRunWhenDone()
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial();

        $queue->expects('hasPendingUrls')->twice()->andReturnValues([false, true]);
        $this->command()->expects('queue')->andReturns($queue);
        $this->command()->expects('whenDone')->never();

        $this->artisan('jukebox:test');
    }

    public function testShouldClearQueueProperly(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial();
        $queue->expects('clear');

        $this->command()->expects('queue')->andReturns($queue);
        $this->command()->expects('whenClearingCache');

        $this->artisan('jukebox:test', ['-c' => null])
            ->expectsOutput('Cache cleared!');
    }

    public function testShouldClearQueueAndExit(): void
    {
        $queue = Mockery::mock(JukeboxBaseQueue::class)->makePartial();
        $queue->expects('clear');

        $this->command()->expects('queue')->andReturns($queue);
        $this->command()->expects('whenClearingCache');

        $this->artisan('jukebox:test', ['-C' => null])
            ->expectsOutput('Cache cleared!');
    }

    public function providesIntegers(): array
    {
        return [
            [10],
            [50],
            [2000],
        ];
    }

    /**
     * @param int $expected
     * @return void
     * @dataProvider providesIntegers
     */
    public function testShouldSetCurrentCrawlLimit(int $expected): void
    {
        $this->manager->expects('setCurrentCrawlLimit')->with($expected);
        $this->artisan('jukebox:test', ['-l' => $expected]);
    }

    /**
     * @param int $expected
     * @return void
     * @dataProvider providesIntegers
     */
    public function testShouldSetTotalCrawlLimit(int $expected): void
    {
        $this->manager->expects('setTotalCrawlLimit')->with($expected);
        $this->artisan('jukebox:test', ['-t' => $expected]);
    }

    /**
     * @param int $expected
     * @return void
     * @dataProvider providesIntegers
     */
    public function testShouldSetDelay(int $expected): void
    {
        $this->manager->expects('setDelayBetweenRequests')->with($expected, null);

        $this->artisan('jukebox:test', ['-d' => $expected]);
    }

    /**
     * @param int $expected
     * @return void
     * @dataProvider providesIntegers
     */
    public function testShouldSetRandomDelay(int $expected): void
    {
        $expectedMax = mt_rand($expected, $expected + 20);

        $this->manager->expects('setDelayBetweenRequests')->withArgs([$expected, $expectedMax]);

        $this->artisan('jukebox:test', ['-d' => $expected . ',' . $expectedMax]);
    }

    public function testShouldQueueSingleUrl(): void
    {
        $this->app->bind(Client::class, function () {
            $mock = new MockHandler([
                new Response(200),
            ]);

            $handlerStack = HandlerStack::create($mock);
            return new Client(['handler' => $handlerStack]);
        });
        $url = 'https://www.tweakers.net/';
        $this->manager->shouldReceive('startCrawling')->with(new Uri($url));

        $this->command()->expects('queue')->never();
        $this->artisan('jukebox:test', ['-u' => $url]);
    }

    public function testShouldDisableRandom(): void
    {
        $this->manager->expects('disableRandom');

        $this->artisan('jukebox:test', ['--no-random' => null]);
    }

    public function testShouldEnableRandom(): void
    {
        $this->manager->expects('enableRandom');

        $this->artisan('jukebox:test', ['--random' => null]);
    }
}


