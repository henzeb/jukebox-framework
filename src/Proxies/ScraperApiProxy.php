<?php

namespace Henzeb\Jukebox\Proxies;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Henzeb\Jukebox\Proxies\Contracts\RequestProxy;

class ScraperApiProxy implements RequestProxy
{
    private const API_DOMAIN = 'https://api.scraperapi.com/';

    public function __construct(private string $key, private array $options = [])
    {
    }

    public function wrap(RequestInterface $request): RequestInterface
    {
        return $request->withUri(
            new Uri(
                $this->buildUri($request->getUri())
            )
        );
    }

    private function buildUri(UriInterface $uri): string
    {
        return sprintf(
            '%s?%s',
            self::API_DOMAIN,
            http_build_query(
                array_merge($this->options, ['api_key' => $this->key, 'url' => (string)$uri])
            )
        );
    }

}
