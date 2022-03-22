<?php

namespace Henzeb\Jukebox\Tests\Unit\Proxies;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Henzeb\Jukebox\Proxies\ScraperApiProxy;

class ScraperApiProxyTest extends TestCase
{
    public function testShouldModifyUri(): void
    {
        $request = new Request('GET', 'https://www.google.nl/');
        $proxy = new ScraperApiProxy('key');

        $this->assertEquals('https://api.scraperapi.com/?api_key=key&url=https%3A%2F%2Fwww.google.nl%2F',
            (string)$proxy->wrap(
                $request
            )->getUri()
        );
    }

    public function testShouldUseSpecifiedKey(): void
    {
        $request = new Request('GET', 'https://www.google.nl/');
        $proxy = new ScraperApiProxy('randomKey');

        $this->assertEquals('https://api.scraperapi.com/?api_key=randomKey&url=https%3A%2F%2Fwww.google.nl%2F',
            (string)$proxy->wrap(
                $request
            )->getUri()
        );
    }

    public function testShouldUseOptions(): void
    {
        $request = new Request('GET', 'https://www.google.nl/');
        $proxy = new ScraperApiProxy('randomKey', ['premium' => true, 'keep_headers' => true]);

        $this->assertEquals(
            'https://api.scraperapi.com/?premium=1&keep_headers=1&api_key=randomKey&url=https%3A%2F%2Fwww.google.nl%2F',
            (string)$proxy->wrap(
                $request
            )->getUri()
        );
    }

    public function testShouldUseCorrectMethod(): void
    {
        $request = new Request('POST', 'https://www.google.nl/');
        $proxy = new ScraperApiProxy('randomKey');

        $this->assertEquals('POST', $proxy->wrap($request)->getMethod());
    }

    public function testShouldAllowHeaders(): void
    {
        $request = new Request('GET', 'https://www.google.nl/', ['header' => 'aHeader']);
        $proxy = new ScraperApiProxy('randomKey');

        $this->assertEquals(['aHeader'], $proxy->wrap($request)->getHeader('header'));
    }

    public function testShouldAllowBody(): void
    {
        $request = new Request('GET', 'https://www.google.nl/', [], 'This is a body');
        $proxy = new ScraperApiProxy('randomKey');

        $this->assertEquals('This is a body', (string)$proxy->wrap($request)->getBody());
    }
}
