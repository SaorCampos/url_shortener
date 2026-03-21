<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Persistence\Eloquent\Mappers\ShortUrlMapper;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;

class EloquentShortUrlRepository implements ShortUrlRepository
{
    public function save(ShortUrl $url): ShortUrl
    {
        $model = $url->id()
            ? ShortUrlModel::find($url->id())
            : null;
        if (!$model) {
            $model = new ShortUrlModel();
            $model->id = $url->id();
        }
        $model->original_url = $url->originalUrl();
        $model->short_code = $url->shortCode();
        $model->clicks = $url->clicks();
        $model->expires_at = $url->expiresAt()->format('Y-m-d H:i:s');
        $model->save();
        return ShortUrlMapper::toEntity($model);
    }
    public function findByCode(string $code): ?ShortUrl
    {
        $model = ShortUrlModel::where('short_code', $code)->first();
        if (!$model) {
            return null;
        }
        return ShortUrlMapper::toEntity($model);
    }
    public function findById(int $id): ?ShortUrl
    {
        $model = ShortUrlModel::find($id);
        if (!$model) {
            return null;
        }
        return ShortUrlMapper::toEntity($model);
    }
}
