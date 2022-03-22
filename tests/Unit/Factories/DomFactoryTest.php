<?php

namespace Henzeb\Jukebox\Tests\Unit\Factories;

use Mockery;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Psr\Http\Message\ResponseInterface;
use Henzeb\Jukebox\Factories\DomFactory;

class DomFactoryTest extends TestCase
{
    public function testShouldReturnDomCrawlerFromString(): void
    {
        $dom = (new DomFactory())->fromString('<p>test</p>');
        $this->assertEquals('<body><p>test</p></body>', $dom->html());
    }

    public function testShouldReturnDomCrawlerFromResponse(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->expects('getBody')->andReturn('<p>test</p>');

        $dom = (new DomFactory())->from($response);
        $this->assertEquals('<body><p>test</p></body>', $dom->html());
    }

    public function testShouldReturnDomCrawlerFromFile(): void
    {
        File::expects('get')
            ->with('/tmp/file.html')
            ->andReturn('<p>test</p>');

        $dom = (new DomFactory())->fromFile('/tmp/file.html');
        $this->assertEquals('<body><p>test</p></body>', $dom->html());
    }
}
