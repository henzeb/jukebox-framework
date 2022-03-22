<?php

namespace Henzeb\Jukebox\Proxies\Contracts;

use Psr\Http\Message\RequestInterface;

interface RequestProxy
{
    public function wrap(RequestInterface $request): RequestInterface;
}
