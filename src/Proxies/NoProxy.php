<?php

namespace Henzeb\Jukebox\Proxies;

use Psr\Http\Message\RequestInterface;
use Henzeb\Jukebox\Proxies\Contracts\RequestProxy;

class NoProxy implements RequestProxy
{
    public function wrap(RequestInterface $request): RequestInterface
    {
        return $request;
    }
}
