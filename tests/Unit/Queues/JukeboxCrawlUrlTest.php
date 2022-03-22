<?php

namespace Henzeb\Jukebox\Tests\Unit\Common\Queues;


use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Spatie\Crawler\CrawlUrl;
use PHPUnit\Framework\TestCase;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;

class JukeboxCrawlUrlTest extends TestCase
{
    public function testAcceptsRequestObject(): void
    {
        $expected = new Request('POST', 'https://www.google.nl');
        $actual = JukeboxCrawlUrl::create($expected);

        $this->assertEquals((string)$expected->getUri(), $actual->url);
        $this->assertEquals($expected, $actual->getRequest());
    }

    public function testAcceptsUriObject(): void
    {
        $expected = new Uri('https://www.tweakers.net/');
        $actual = JukeboxCrawlUrl::create($expected);

        $this->assertEquals((string)$expected, $actual->url);
        $this->assertEquals($expected, $actual->getRequest()->getUri());
    }

    public function testFromCrawlUrl(): void
    {
        $expectedUrl = new Uri('https://www.tweakers.net/');
        $expectedFoundOn = new Uri('https://www.tweakers.net/');
        $expected = CrawlUrl::create($expectedUrl, $expectedFoundOn, 3);

        $actual = JukeboxCrawlUrl::from($expected);

        $this->assertEquals(
            $expected->url, $actual->url
        );

        $this->assertEquals(
            $expected->foundOnUrl, $actual->foundOnUrl
        );
    }

    public function testFromUri(): void
    {
        $expectedUrl = new Uri('https://www.tweakers.net/');

        $actual = JukeboxCrawlUrl::from($expectedUrl);

        $this->assertEquals(
            $expectedUrl, $actual->url
        );
    }

    public function testFromRequest(): void
    {
        $expectedUrl = new Uri('https://www.tweakers.net/');

        $request = new Request('POST', $expectedUrl, [], 'myBody');

        $actual = JukeboxCrawlUrl::from($request);

        $this->assertEquals(
            $expectedUrl, $actual->url
        );

        $this->assertTrue(
            $request === $actual->getRequest()
        );
    }

    public function testShouldSerialize(): void
    {
        $expectedUrl = new Request('POST', new Uri('https://www.google.nl/'), ['headers' => 'header'], 'myBody');
        $expectedFoundOnUrl = new Uri('https://www.google.nl/');
        $crawlUrl = JukeboxCrawlUrl::create($expectedUrl, $expectedFoundOnUrl, 12);
        $actualCrawlUrl = unserialize(serialize($crawlUrl));

        $this->assertEquals($crawlUrl->url, $actualCrawlUrl->url);
        $this->assertEquals($crawlUrl->foundOnUrl, $actualCrawlUrl->foundOnUrl);
        $this->assertEquals($crawlUrl->getId(), $actualCrawlUrl->getId());
        $this->assertEquals((string)$crawlUrl->getRequest()->getBody(), (string)$actualCrawlUrl->getRequest()->getBody());
        $this->assertEquals($crawlUrl->getRequest()->getMethod(), $actualCrawlUrl->getRequest()->getMethod());
        $this->assertEquals($crawlUrl->getRequest()->getUri(), $actualCrawlUrl->getRequest()->getUri());
        $this->assertEquals($crawlUrl->getRequest()->getHeaders(), $actualCrawlUrl->getRequest()->getHeaders());


    }
}
