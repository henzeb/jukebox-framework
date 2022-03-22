<?php

namespace Henzeb\Jukebox\Queues\Contracts;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Illuminate\Database\Eloquent\Model;
use Henzeb\Jukebox\Queues\JukeboxCrawlUrl;

abstract class JukeboxQueueModel extends Model
{
    protected string $queueIdColumn = 'queue';
    protected string $urlColumn = 'url';
    protected string $triesColumn = 'tries';
    protected string $priorityColumn = 'priority';
    protected string $completedColumn = 'completed';

    private static function new(): self
    {
        return new static();
    }

    private static function getModel(JukeboxCrawlUrl $crawlUrl, string $queueIdentifier): self
    {
        if ($model = $crawlUrl->getMeta('model')) {
            return $model;
        }

        $model = self::new();

        if ($crawlUrl->getId()) {
            $query = self::where($model->primaryKey, $crawlUrl->getId());
        } else {
            $query = self::where($model->urlColumn, $crawlUrl->getRequest()->getUri());
        }

        return $query->where($model->queueIdColumn, $queueIdentifier)
            ->first();
    }

    public static function add(JukeboxCrawlUrl $crawlUrl, string $queueIdentifier, bool $priority = false): void
    {
        $model = self::new();
        $model->setDefaultAttributes();

        if ($crawlUrl->getId()) {
            $model->setAttribute($model->primaryKey, $crawlUrl->getId());

            $model = self::getById($crawlUrl->getId(), $queueIdentifier)?->getMeta('model') ?? $model;
        }

        if ($model->getAttribute($model->completedColumn)) {
            return;
        }

        $model->setAttribute($model->queueIdColumn, $queueIdentifier);
        $model->setAttribute($model->urlColumn, (string)$crawlUrl->getRequest()->getUri());
        $model->setAttribute($model->priorityColumn, $priority);
        $model->setAttribute($model->triesColumn, 0);
        $model->setAttribute($model->completedColumn, 0);

        $model->save();
    }

    protected function setDefaultAttributes(): void
    {
    }

    public static function getById($id, string $identifier): ?JukeboxCrawlUrl
    {
        $model = self::new();

        $model = self::where($model->primaryKey, $id)
            ->where($model->queueIdColumn, $identifier)
            ->first();

        if ($model) {
            return $model->toCrawlUrl();
        }

        return null;
    }

    public static function getByUrl(UriInterface|string $url): ?self
    {
        return self::where('url', (string)$url)->first();
    }

    public static function has(JukeboxCrawlUrl $crawlUrl, string $queueIdentifier): bool
    {
        $query = self::query();
        $model = self::new();

        if ($crawlUrl->getId()) {
            $query->where($model->primaryKey, $crawlUrl->getId());
        }

        $query->where($model->queueIdColumn, $queueIdentifier)
            ->where($model->urlColumn, (string)$crawlUrl->getRequest()->getUri());

        return $query->count() > 0;
    }

    public static function countPendingUrls(string $identifier): int
    {
        $model = self::new();
        return self::query()
            ->where($model->queueIdColumn, $identifier)
            ->where($model->completedColumn, false)
            ->count();
    }

    public static function countProcessedUrls(string $identifier): int
    {
        $model = self::new();
        return self::query()
            ->where($model->queueIdColumn, $identifier)
            ->where($model->completedColumn, true)
            ->count();
    }

    public static function getPending(string $identifier, int $chunkSize, bool $randomly): array
    {
        $model = self::new();
        $query = self::query()
            ->where($model->queueIdColumn, $identifier)
            ->where($model->completedColumn, false)
            ->limit($chunkSize)
            ->orderByDesc($model->priorityColumn);

        if ($randomly) {
            $query->inRandomOrder();
        }

        return $query->get()
            ->map(fn(self $model) => $model->toCrawlurl())
            ->toArray();

    }

    public static function markAsProcessed(JukeboxCrawlUrl $crawlUrl, string $queueIdentifier): void
    {
        $model = $crawlUrl->getMeta('model') ?? self::find($crawlUrl->getId());

        if ($model && $model->getAttribute($model->queueIdColumn, $queueIdentifier)) {
            $model->setAttribute($model->completedColumn, true);
            $model->save();
        }
    }

    public static function retry(JukeboxCrawlUrl $crawlUrl, string $queueIdentifier, int $tries): void
    {
        $model = self::getModel($crawlUrl, $queueIdentifier);

        if ($model->getAttribute($model->triesColumn) >= $tries) {
            return;
        }

        $model->setAttribute($model->triesColumn, $model->getAttribute($model->triesColumn) + 1);
        $model->save();
    }

    public static function deleteCompleted(string $queueIdentifier): void
    {
        $model = self::new();

        self::where($model->queueIdColumn, $queueIdentifier)
            ->where($model->completedColumn, true)
            ->delete();
    }

    public static function truncate(): void
    {
        Model::query()->delete();
    }

    protected function toCrawlUrl(): JukeboxCrawlUrl
    {
        return JukeboxCrawlUrl::create(
            new Uri($this->getAttribute($this->urlColumn)),
            id: $this->getAttribute($this->primaryKey),
        )->addMeta('model', $this);
    }
}
