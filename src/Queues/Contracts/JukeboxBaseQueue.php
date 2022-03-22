<?php

namespace Henzeb\Jukebox\Queues\Contracts;

use Error;
use Closure;
use DateTime;
use Carbon\Carbon;
use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Henzeb\Console\Facades\Console;
use Spatie\Crawler\CrawlQueues\CrawlQueue;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;
use Spatie\Crawler\Exceptions\UrlNotFoundByIndex;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class JukeboxBaseQueue extends JukeboxQueue
{
    private string $urlsCacheKey = 'urls';
    private string $pendingUrlsCacheKey = 'pendingUrls';

    private bool $random = true;
    private Closure $randomGenerator;

    protected array $pendingUrls = [];
    protected array $urls = [];

    public function __construct(string $identifier, private int $retry = 1)
    {
        $this->setCacheKeys($identifier);

        $this->refresh();

        $this->setRandomGenerator();
    }

    protected abstract function getUrls(string $key): array;

    protected abstract function getPendingUrls(string $key): array;

    protected abstract function writeUrls(string $key, array $urls): void;

    protected abstract function writePendingUrls(string $key, array $pendingUrls): void;

    protected abstract function clearCache(string $key): void;

    protected function writeToCache(): void
    {
        $this->writeUrls($this->urlsCacheKey, $this->urls);

        $this->writePendingUrls($this->pendingUrlsCacheKey, $this->pendingUrls);
    }

    public function clear(): void
    {
        $this->lock(function () {
            $this->urls = [];
            $this->pendingUrls = [];

            $this->clearCache($this->urlsCacheKey);
            $this->clearCache($this->pendingUrlsCacheKey);
        });
    }

    protected function refresh(): void
    {
        $this->urls = $this->getUrls($this->urlsCacheKey);
        $this->pendingUrls = $this->getPendingUrls($this->pendingUrlsCacheKey);
    }

    private function setCacheKeys(string $identifier): void
    {
        $identifier = md5($identifier);

        $this->urlsCacheKey = $identifier . '_urls';
        $this->pendingUrlsCacheKey = $identifier . '_pendingUrls';
    }

    public function add(CrawlUrl $crawlUrl): JukeboxQueue
    {
        return $this->lock(function () use ($crawlUrl) {
            $crawlUrl = JukeboxCrawlUrl::from($crawlUrl);

            $id = $this->generateId($crawlUrl);

            if (!isset($this->urls[$id])) {
                $crawlUrl->setId($id);

                $this->urls[$id] = $crawlUrl;
                $this->pendingUrls[$id] = $crawlUrl;

                $this->writeToCache();
            }

            return $this;
        });
    }

    public function remove(CrawlUrl $crawlUrl): void
    {
        $this->lock(function () use ($crawlUrl) {
            $id = $this->generateId($crawlUrl);

            unset($this->pendingUrls[$id]);
            unset($this->urls[$id]);

            $this->writeToCache();
        });
    }

    public function hasPendingUrls(): bool
    {
        return $this->lock(function () {
            return (bool)$this->getPendingUrlsReadyForProcess($this->pendingUrls);
        });
    }

    public function getUrlById($id): JukeboxCrawlUrl
    {
        return $this->lock(function () use ($id) {
            if (!isset($this->urls[$id])) {
                throw new UrlNotFoundByIndex("Crawl url {$id} not found in collection.");
            }

            return $this->urls[$id];
        });
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $crawlUrl): bool
    {
        return $this->lock(function () use ($crawlUrl) {
            $id = $this->generateId($crawlUrl);

            if (isset($this->pendingUrls[$id])) {
                return false;
            }

            if (isset($this->urls[$id])) {
                return true;
            }

            return false;
        });
    }

    public function markAsProcessed(CrawlUrl $crawlUrl): void
    {
        $this->lock(function () use ($crawlUrl) {
            $id = $this->generateId($crawlUrl);

            if (isset($this->pendingUrls[$id])) {
                unset($this->pendingUrls[$id]);
            }

            if (isset($this->urls[$id])) {
                $this->writePendingUrls($this->pendingUrlsCacheKey, $this->pendingUrls);
            } else {
                $this->urls[$id] = JukeboxCrawlUrl::from($crawlUrl);
                $this->writeToCache();
            }
        });

    }


    public function setMaxTries(int $tries): self
    {
        $this->retry = $tries;

        return $this;
    }

    public function retry(CrawlUrl|UriInterface $crawlUrl): void
    {
        $this->lock(function () use ($crawlUrl) {
            $id = $this->generateId($crawlUrl);

            try {
                $crawlUrl = $this->getUrlById($id);
            } catch (UrlNotFoundByIndex) {
                $crawlUrl = JukeboxCrawlUrl::from($crawlUrl);
                $crawlUrl->setId($id);
            }

            if ($crawlUrl->try() >= $this->retry) {
                return;
            }

            unset($this->urls[$crawlUrl->getId()]);
            unset($this->pendingUrls[$crawlUrl->getId()]);
            $delayUntil = $crawlUrl->try() * 30;

            $crawlUrl->retry();
            $crawlUrl->delayUntil(Carbon::now()->addSeconds($delayUntil));

            Console::error(sprintf('retry: %d, for %d seconds', $crawlUrl->try(), $delayUntil), OutputInterface::VERBOSITY_VERBOSE);

            $this->urls[$crawlUrl->getId()] = $crawlUrl;
            $this->pendingUrls[$crawlUrl->getId()] = $crawlUrl;
            $this->writeToCache();
        });
    }

    public function getPendingUrlCount(): int
    {
        return $this->lock(function () {
            return count($this->pendingUrls);
        });
    }

    public function getProcessedUrlCount(): int
    {
        return $this->lock(function () {
            return count($this->urls) - count($this->pendingUrls);
        });
    }

    public function has(CrawlUrl|UriInterface $crawlUrl): bool
    {
        return $this->lock(function () use ($crawlUrl) {
            return isset($this->urls[$this->generateId($crawlUrl)]);
        });
    }

    public function getPendingUrl(): ?JukeboxCrawlUrl
    {
        return $this->lock(function () {
            $pending = null;

            if ($this->random === false) {
                $pendingUrls = $this->getPendingUrlsReadyForProcess($this->pendingUrls);
                $pending = reset($pendingUrls);
            }

            if ($this->random === true) {
                $pending = $this->getPendingUrlRandomly();
            }

            return $pending ?: null;
        });
    }

    private function getPendingUrlsReadyForProcess(array $pendingUrls): array
    {
        $now = new DateTime();
        return array_filter($pendingUrls, function (JukeboxCrawlUrl $crawLUrl) use ($now): bool {
            if($date = $crawLUrl->delayedUntil()) {
                return new $now >= $date;
            }
            return true;
        });
    }

    private function getPendingUrlRandomly(): ?CrawlUrl
    {
        return $this->lock(function () {
            if ($key = ($this->randomGenerator)($this->getPendingUrlsReadyForProcess($this->pendingUrls))) {
                return $this->pendingUrls[$key];
            }
            return null;
        });
    }

    private function generateId(CrawlUrl|UriInterface $crawlUrl): string
    {
        $crawlUrl = JukeboxCrawlUrl::from($crawlUrl);
        $request = $crawlUrl->getRequest();

        /**
         * In the original CrawlUrl, an id not set causes an Error
         */
        try {
            if ($id = $crawlUrl->getId()) {
                return $id;
            }
        } catch (Error) {
        }

        return md5(
            $request->getUri() .
            $request->getMethod() .
            $request->getBody() .
            json_encode($request->getHeaders())
        );
    }

    public function disableRandom(): void
    {
        $this->random = false;
    }

    public function enableRandom(): void
    {
        $this->random = true;
    }

    private function setRandomGenerator(): void
    {
        $this->randomGenerator = function (array $items) {
            return !empty($items) ? array_rand($items) : null;
        };
    }

    protected function lock(Closure $closure): mixed
    {
        return $closure();
    }
}
