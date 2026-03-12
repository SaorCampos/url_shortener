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
        $model = ShortUrlMapper::toModel($url);
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
