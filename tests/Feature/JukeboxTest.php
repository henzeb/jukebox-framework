<?php

namespace Henzeb\Jukebox\Tests\Feature;


use Mockery;
use Spatie\Crawler\Crawler;
use Henzeb\Jukebox\Jukebox;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Henzeb\Jukebox\Profiles\JukeboxProfile;
use Henzeb\Jukebox\Crawlers\JukeboxCrawler;
use Henzeb\Jukebox\Observers\JukeboxObserver;
use Henzeb\Jukebox\Observers\JukeboxCrawlObserver;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsTrue;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsFalse;

class JukeboxTest extends TestCase
{
    public function testShouldRun()
    {

        $httpHandler = new MockHandler([
            new Response(200, [], 'Hello, World'),
        ]);

        /**
         * @var $notCalled JukeboxCrawlObserver
         * @var $called JukeboxCrawlObserver
         */
        $notCalled = Mockery::mock(JukeboxCrawlObserver::class);
        $notCalled->expects('willCrawl')->never();
        $notCalled->expects('crawled')->never();
        $notCalled->expects('crawlFailed')->never();

        $called = Mockery::mock(JukeboxCrawlObserver::class);
        $called->expects('willCrawl')->once();
        $called->expects('crawled')->once()
            ->andReturnUsing(function (UriInterface $uri, ResponseInterface $response) {
                $this->assertEquals('Hello, World', (string)$response->getBody());
            });
        $called->expects('crawlFailed')->never();

        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), $notCalled->makePartial());
        $jukebox->add(new ProfileReturnsTrue(), $called->makePartial());

        JukeboxCrawler::create(['handler' => $httpHandler])
            ->setCrawlObserver(new JukeboxObserver(
                $jukebox
            ))
            ->setCrawlProfile(new JukeboxProfile(
                $jukebox
            ))->startCrawling('https://www.websitedoesnotexist.nl');
    }

    public function testShouldFail()
    {
        $exception = new RequestException('Error Communicating with Server', new Request('GET', 'test'));
        $httpHandler = new MockHandler([
            $exception
        ]);
        /**
         * @var $notCalled JukeboxCrawlObserver
         * @var $called JukeboxCrawlObserver
         */
        $notCalled = Mockery::mock(JukeboxCrawlObserver::class);
        $notCalled->expects('willCrawl')->never();
        $notCalled->expects('crawled')->never();
        $notCalled->expects('crawlFailed')->never();

        $called = Mockery::mock(JukeboxCrawlObserver::class);
        $called->expects('willCrawl')->once();
        $called->expects('crawled')->never();
        $called->expects('crawlFailed')->once()->withSomeOfArgs($exception);

        $jukebox = new Jukebox();
        $jukebox->add(new ProfileReturnsFalse(), $notCalled->makePartial());
        $jukebox->add(new ProfileReturnsTrue(), $called->makePartial());

        JukeboxCrawler::create(['handler' => $httpHandler])
            ->setCrawlObserver(
                new JukeboxObserver(
                    $jukebox
                )
            )->setCrawlProfile(
                new JukeboxProfile(
                    $jukebox
                )
            )->startCrawling('https://www.google.nl/');
    }


}
