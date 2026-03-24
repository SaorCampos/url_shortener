<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Persistence\Eloquent\Mappers\ShortUrlMapper;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Support\Facades\Redis;

class EloquentShortUrlRepository implements ShortUrlRepository
{
    public function save(ShortUrl $url): ShortUrl
    {
        $model = ShortUrlModel::updateOrCreate(
            ['id' => $url->id()],
            [
                'original_url' => $url->originalUrl(),
                'short_code'   => $url->shortCode(),
                'clicks'       => $url->clicks(),
                'expires_at'   => $url->expiresAt(),
            ]
        );
        return ShortUrlMapper::toEntity($model);
    }
    public function findByCode(string $code): ?ShortUrl
    {
        $model = ShortUrlModel::where('short_code', $code)->first();
        if (!$model) {
            return null;
        }
        $redisClicks = (int) Redis::get("shorturl:clicks:total:{$code}");
        if ($redisClicks > $model->clicks) {
            $model->clicks = $redisClicks;
        }
        return ShortUrlMapper::toEntity($model);
    }
    public function findById(string $id): ?ShortUrl
    {
        $model = ShortUrlModel::find($id);
        if (!$model) {
            return null;
        }
        return ShortUrlMapper::toEntity($model);
    }
    public function findByOriginalUrl(string $url): ?ShortUrl
    {
        $model = ShortUrlModel::where('original_url', $url)->first();
        if (!$model) {
            return null;
        }
        return ShortUrlMapper::toEntity($model);
    }
}
