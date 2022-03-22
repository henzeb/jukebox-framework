<?php

namespace Henzeb\Jukebox\Factories;

use File;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

class DomFactory
{
    public function from(ResponseInterface $response): Crawler
    {
        return $this->fromString((string)$response->getBody());
    }

    public function fromString(string $content): Crawler
    {
        return new Crawler($content);
    }

    public function fromFile(string $path): Crawler
    {
        return $this->fromString(File::get($path));
    }
}
