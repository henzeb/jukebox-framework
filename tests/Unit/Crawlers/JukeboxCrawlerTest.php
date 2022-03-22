<?php
namespace Henzeb\Jukebox\Tests\Unit\Crawlers;

use Mockery;
use GuzzleHttp\Client;
use ReflectionProperty;
use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\CrawlUrl;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Handler\MockHandler;
use Henzeb\Jukebox\Queues\ArrayCrawlQueue;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Henzeb\Jukebox\Crawlers\JukeboxCrawler;
use Henzeb\Jukebox\Managers\JukeboxManager;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Henzeb\Jukebox\Observers\JukeboxCrawlObserver;
use Henzeb\Jukebox\Proxies\Contracts\RequestProxy;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsTrue;
use Henzeb\Jukebox\Tests\Fixtures\ProfileReturnsFalse;

class JukeboxCrawlerTest extends MockeryTestCase
{
    public function testShouldGetSameDelayBetweenTests(): void
    {
        $crawler = new JukeboxCrawler(new Client());
        $crawler->setDelayBetweenRequests(1);
        $this->assertEquals($crawler->getDelayBetweenRequests(), $crawler->getDelayBetweenRequests());
    }

    public function testShouldGetRandomDelayBetweenTests(): void
    {
        $crawler = new JukeboxCrawler(new Client());
        $crawler->setDelayBetweenRequests(1, 5);

        $random = new ReflectionProperty($crawler, 'randomDelayGenerator');

        $random->setValue($crawler, fn() => 1);
        $expected = $crawler->getDelayBetweenRequests();
        $random->setValue($crawler, fn() => 5);

        $this->assertNotEquals($expected, $crawler->getDelayBetweenRequests());
    }

    public function testShouldAddToQueue(): void
    {
        $crawlUrl = JukeboxCrawlUrl::create(new Uri('www.google.nl'));
        $crawler = new JukeboxCrawler(new Client());

        $queue = Mockery::mock(ArrayCrawlQueue::class);
        $queue->expects('has')->andReturnFalse();
        $queue->expects('add')->with($crawlUrl);

        $crawler->setCrawlQueue($queue);

        $actualCrawler = $crawler->addToCrawlQueue($crawlUrl);
        $this->assertTrue($crawler === $actualCrawler);
    }

    public function testShouldAddRequestToQueue(): void
    {
        $request = new Request('POST', new Uri('www.google.nl'));

        $crawlUrl = JukeboxCrawlUrl::create($request);

        $crawler = new JukeboxCrawler(new Client());

        $queue = Mockery::mock(ArrayCrawlQueue::class);
        $queue->expects('has')->andReturnFalse();
        $queue->expects('add')->withArgs(fn($actual) => $crawlUrl == $actual);

        $crawler->setCrawlQueue($queue);

        $actualCrawler = $crawler->addToCrawlQueue($request);
        $this->assertTrue($crawler === $actualCrawler);

    }

    public function testShouldAddUrlRequestToQueue(): void
    {
        $request = new Uri('www.google.nl');

        $crawlUrl = JukeboxCrawlUrl::create($request);

        $crawler = new JukeboxCrawler(new Client());

        $queue = Mockery::mock(ArrayCrawlQueue::class);
        $queue->expects('has')->andReturnFalse();
        $queue->expects('add')->withArgs(fn($actual) => $crawlUrl == $actual);

        $crawler->setCrawlQueue($queue);

        $actualCrawler = $crawler->addToCrawlQueue($request);
        $this->assertTrue($crawler === $actualCrawler);

    }

    public function testShouldNotAddToQueueIfExists(): void
    {
        $crawlUrl = CrawlUrl::create(new Uri('www.google.nl'));
        $crawler = new JukeboxCrawler(new Client());

        $queue = Mockery::mock(ArrayCrawlQueue::class);
        $queue->expects('has')->andReturnTrue();
        $queue->expects('add')->never();

        $crawler->setCrawlQueue($queue);

        $actualCrawler = $crawler->addToCrawlQueue($crawlUrl);

        $this->assertTrue($crawler === $actualCrawler);

    }

    public function testShouldNotAddToQueueIfProfileSaysNo(): void
    {
        $crawlUrl = CrawlUrl::create(new Uri('www.google.nl'));
        $crawler = new JukeboxCrawler(new Client());

        $crawler->setCrawlProfile(new ProfileReturnsFalse());

        $queue = Mockery::mock(ArrayCrawlQueue::class);
        $queue->expects('has')->never();
        $queue->expects('add')->never();

        $crawler->setCrawlQueue($queue);

        $actualCrawler = $crawler->addToCrawlQueue($crawlUrl);

        $this->assertTrue($crawler === $actualCrawler);

    }

    public function testGetCrawlRequests(): void
    {
        $queue = Mockery::mock(ArrayCrawlQueue::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $queue->expects('getUrls')->andReturn([]);

        $queue->__construct('test');

        $items = [
            'b15b3fd2e6ae3c2f12f038322d38b7e1' => new Request('POST', new Uri('www.google.nl')),
            '5ae62b56c16a0bc37527a04221ed46d6' => new Request('POST', new Uri('www.google.nl'), [], 'body'),
            '8b1b3b963e4e1f3c3fb59b897af7602d' => JukeboxCrawlUrl::create(new Uri('www.google.com')),
            '980e3509a35ea1e1fd8cc8c183524cdf' => CrawlUrl::create(new Uri('www.google.de')),
        ];

        $crawler = new class(new Client()) extends JukeboxCrawler {

            public function getCrawlRequests(): \Generator
            {
                return parent::getCrawlRequests();
            }
        };
        $crawler->setCrawlProfile(new ProfileReturnsTrue());
        $crawler->setCrawlQueue($queue);

        foreach ($items as $item) {
            $crawler->addToCrawlQueue($item);
        }

        foreach ($crawler->getCrawlRequests() as $key => $actual) {
            $expected = $items[$key];

            if ($expected instanceof CrawlUrl) {
                $expected = JukeboxCrawlUrl::from($expected);
            }

            if ($expected instanceof JukeboxCrawlUrl) {
                $expected = $expected->getRequest();
            }

            $this->assertEquals($expected->getMethod(), $actual->getMethod());
            $this->assertEquals($expected->getUri(), $actual->getUri());
            $this->assertEquals($expected->getBody()->getContents(), $actual->getBody()->getContents());
        }
    }

    public function testShouldCrawlFromCrawlQueueWhenNullProvided(): void
    {
        $crawler = new JukeboxCrawler(new Client());

        $observer = Mockery::mock(JukeboxCrawlObserver::class)->makePartial();
        $observer->expects('finishedCrawling');
        $crawler->addCrawlObserver($observer);

        $queue = Mockery::mock(ArrayCrawlQueue::class)->makePartial();
        $queue->expects('hasPendingUrls')->passthru();
        $crawler->setCrawlQueue($queue);

        $crawler->startCrawling();
    }

    public function testShouldDisableRandomOnQueue(): void
    {
        $crawler = new JukeboxCrawler(new Client());

        $queue = Mockery::mock(ArrayCrawlQueue::class);
        $queue->expects('disableRandom');
        $crawler->setCrawlQueue($queue);

        $crawler->disableRandom();
    }

    public function testShouldEnableRandomOnQueue(): void
    {
        $crawler = new JukeboxCrawler(new Client());

        $queue = Mockery::mock(ArrayCrawlQueue::class);
        $queue->expects('enableRandom');
        $crawler->setCrawlQueue($queue);

        $crawler->enableRandom();
    }

    public function testShouldHaveAccessToCookieJar(): void
    {
        $cookieJar = new CookieJar();
        $crawler = new JukeboxCrawler(new Client(['cookies' => $cookieJar]));

        $this->assertTrue($cookieJar === $crawler->getCookieJar());
    }

    public function testShouldHaveAccessToCookieJarWhenTrueGiven(): void
    {
        $mock = new MockHandler([
            (new Response(200, ['Set-Cookie' => 'foo=bar'], '')),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'cookies' => true]);
        $crawler = new JukeboxCrawler($client);
        $crawler->startCrawling('https://www.google.nl/');

        $cookie = $crawler->getCookieJar()->getCookieByName('foo');

        $this->assertEquals('bar', $cookie->getValue());
    }

    public function testShouldClearCookies(): void
    {
        $mock = new MockHandler([
            (new Response(200)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'cookies' => true]);
        $crawler = new JukeboxCrawler($client);
        $crawler->getCookieJar()->setCookie(new SetCookie([

                "Name" => "foo",
                "Value" => "bar",
                "Domain" => "www.google.nl",
                "Path" => "/",
                "Max-Age" => null,
                "Expires" => null,
                "Secure" => false,
                "Discard" => false,
                "HttpOnly" => false

        ]));
        $crawler->addToCrawlQueue(JukeboxCrawlUrl::create(new Uri('https://www.google.nl/'), clearCookies: true));

        $this->assertEquals(1, $crawler->getCookieJar()->count());
        $crawler->startCrawling();
        $this->assertEquals(0, $crawler->getCookieJar()->count());

    }

    public function testShouldUseGivenProxy(): void
    {
        $mockProxy = Mockery::mock(RequestProxy::class);
        $mockProxy->expects('wrap')->andReturn(new Request('GET', 'https://www.google.nl/'));
        $mock = new MockHandler([
            (new Response(200)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'cookies' => true]);
        $crawler = new JukeboxCrawler($client);
        $crawler->setProxy($mockProxy);
        $crawler->startCrawling(new Uri('https://www.google.nl/'));
    }

    public function testShouldIgnoreProxy(): void
    {
        $mockProxy = Mockery::mock(RequestProxy::class);
        $mockProxy->expects('wrap')->never();
        $mock = new MockHandler([
            (new Response(200)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'cookies' => true]);
        $crawler = new JukeboxCrawler($client);
        $crawler->setProxy($mockProxy);
        $crawler->startCrawling(JukeboxCrawlUrl::create(new Uri('www.google.nl'))->noProxy());
    }

    public function testShouldExecuteOnCrawlStart(): void
    {
        $mock = new MockHandler([
            (new Response(200)),
        ]);
        $actual = false;

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'cookies' => true]);
        $crawler = new JukeboxCrawler($client);
        $crawler->setOnCrawlStart(function() use (&$actual){
            $actual = true;
        });

        $crawler->startCrawling(new Uri('https://www.google.nl/'));

        $this->assertTrue($actual);
    }

    public function testShouldExecuteOnGetCrawlRequests(): void
    {
        $mock = new MockHandler([
            (new Response(200)),
        ]);
        $actual = false;

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'cookies' => true]);
        $crawler = new JukeboxCrawler($client);
        $crawler->setOnGetCrawlRequests(function() use (&$actual){
            $actual = true;
        });

        $crawler->startCrawling(new Uri('https://www.google.nl/'));

        $this->assertTrue($actual);
    }
}
