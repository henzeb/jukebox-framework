<?php

namespace Henzeb\Jukebox\Queues;

use DateTime;
use GuzzleHttp\Psr7\Utils;
use Spatie\Crawler\CrawlUrl;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;

class JukeboxCrawlUrl extends CrawlUrl
{
    private RequestInterface $request;

    private bool $clearCookies = false;
    private array $metaData = [];
    private bool $useProxy = true;
    private int $try = 1;
    private ?DateTime $delayedUntil = null;

    public static function create(
        UriInterface|RequestInterface $request,
        ?UriInterface                 $foundOnUrl = null,
        mixed                         $id = null,
        bool                          $clearCookies = false
    ): static
    {
        if ($request instanceof UriInterface) {
            $request = new Request('GET', $request);
        }

        $object = parent::create($request->getUri(), $foundOnUrl, $id);
        $object->clearCookies = $clearCookies;
        $object->setRequest($request);
        return $object;
    }

    public static function from(CrawlUrl|UriInterface|RequestInterface $crawlUrl): static
    {
        if ($crawlUrl instanceof JukeboxCrawlUrl) {
            return $crawlUrl;
        }

        if ($crawlUrl instanceof CrawlUrl) {
            return static::create($crawlUrl->url, $crawlUrl->foundOnUrl, isset($crawlUrl->id) ? $crawlUrl->getId() : null);
        }

        return static::create($crawlUrl);
    }

    public function getId(): mixed
    {
        return $this->id ?? null;
    }

    private function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function retry(): void
    {
        $this->try += 1;
    }

    public function try(): int
    {
        return $this->try;
    }

    public function delayUntil(DateTime $dateTime): void
    {
        $this->delayedUntil = $dateTime;
    }

    public function delayedUntil(): ?DateTime
    {
        return $this->delayedUntil;
    }

    public function __serialize(): array
    {
        return [
            'request' => $this->request,
            'body' => (string)$this->request->getBody(),
            'foundOnUrl' => $this->foundOnUrl,
            'id' => $this->id ?? null,
            'clearCookies' => $this->clearCookies,
            'useProxy' => $this->useProxy,
            'try' => $this->try,
            'delayedUntil' => $this->delayedUntil,
            'metaData' => $this->metaData,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->request = $data['request']->withBody(Utils::streamFor($data['body']));
        $this->url = $this->request->getUri();

        unset($data['request'], $data['body']);

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function clearCookies(): bool
    {
        return $this->clearCookies;
    }

    public function noProxy(): self
    {
        $this->useProxy = false;
        return $this;
    }

    public function useProxy(): bool
    {
        return $this->useProxy;
    }

    public function addMeta(string $key, mixed $value): self
    {
        $this->metaData[$key] = $value;
        return $this;
    }

    public function getMeta(string $key): mixed
    {
        return $this->metaData[$key] ?? null;
    }

}
