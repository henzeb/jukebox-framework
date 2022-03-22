<?php

namespace Henzeb\Jukebox\Managers;

use GuzzleHttp\Client;
use Spatie\Crawler\Crawler;
use BadMethodCallException;
use Henzeb\Jukebox\Jukebox;
use GuzzleHttp\RequestOptions;
use Henzeb\Jukebox\Crawlers\JukeboxCrawler;
use Henzeb\Jukebox\Observers\EmptyObserver;
use Henzeb\Jukebox\Profiles\JukeboxProfile;
use Henzeb\Jukebox\Observers\JukeboxObserver;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Henzeb\Jukebox\Queues\Contracts\JukeboxQueue;
use Henzeb\Jukebox\Observers\JukeboxCrawlObserver;
use Henzeb\Jukebox\Collections\JukeboxObserverCollection;

/**
 * @mixin JukeboxCrawler
 */
class JukeboxManager
{
    private ?Jukebox $jukebox = null;
    private ?JukeboxCrawler $crawler = null;

    private array $options = [];

    private array $defaultOptions = [
        RequestOptions::TIMEOUT => 30,
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge(
            JukeboxCrawler::defaultClientOptions(),
            $this->defaultOptions,
            $options
        );
    }

    private function jukebox(): Jukebox
    {
        if ($this->jukebox) {
            return $this->jukebox;
        }

        return $this->jukebox = new Jukebox();
    }

    public function add(CrawlProfile $profile, JukeboxCrawlObserver|JukeboxObserverCollection|array $observer = null): self
    {
        $this->jukebox()
            ->add($profile, $observer ?? new EmptyObserver());

        return $this;
    }

    public function profile(): JukeboxProfile
    {
        return new JukeboxProfile($this->jukebox());
    }

    public function observer(): JukeboxObserver
    {
        return new JukeboxObserver($this->jukebox(), $this);
    }

    private function getCrawler(): JukeboxCrawler
    {
        if (null !== $this->crawler) {
            return $this->crawler;
        }

        return $this->crawler = (new JukeboxCrawler(
            resolve(Client::class, ['config' => $this->options])
        ))->setCrawlProfile($this->profile())
            ->setCrawlObserver($this->observer());

    }

    public function setCrawlProfile(
        CrawlProfile                                         $profile,
        JukeboxCrawlObserver|JukeboxObserverCollection|array $observer = null
    ): self
    {
        $this->jukebox = null;

        return $this->add($profile, $observer);
    }

    public function setCrawlObserver(
        JukeboxCrawlObserver|JukeboxObserverCollection|array $observer,
        CrawlProfile                                         $profile,

    ): self
    {
        $this->jukebox = null;

        return $this->add($profile, $observer);
    }

    public function addCrawlObserver(
        JukeboxCrawlObserver|JukeboxObserverCollection|array $observer,
        CrawlProfile                                         $profile,

    ): self
    {
        return $this->add($profile, $observer);
    }

    public function setCrawlQueue(JukeboxQueue $queue): self
    {
        $this->getCrawler()->setCrawlQueue($queue);

        return $this;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $crawler = $this->getCrawler();

        if (method_exists($crawler, $name)) {

            $return = $crawler->$name(...$arguments);

            if ($return instanceof Crawler) {
                return $this;
            }

            return $return;
        }

        throw new BadMethodCallException(
            sprintf('class \'%s\' does not have method \'%s\'', self::class, $name)
        );
    }
}
