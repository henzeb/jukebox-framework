<?php

namespace Henzeb\Jukebox\Tests\Unit\Proxies;

use GuzzleHttp\Psr7\Request;
use Henzeb\Jukebox\Proxies\NoProxy;
use PHPUnit\Framework\TestCase;

class NoProxyTest extends TestCase
{
    public function testShouldReturnSameRequest() {
        $request = new Request('GET', 'https://www.google.nl');
        $this->assertTrue(
            $request === (new NoProxy())->wrap($request)
        );
    }
}
