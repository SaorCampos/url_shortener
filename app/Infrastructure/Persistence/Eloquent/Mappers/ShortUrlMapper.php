<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;

class ShortUrlMapper
{
    public static function toEntity(ShortUrlModel $model): ShortUrl
    {
        return ShortUrl::restore(
            $model->id,
            $model->original_url,
            $model->short_code,
            $model->clicks,
            $model->expires_at ? new \DateTimeImmutable($model->expires_at) : null
        );
    }

    public static function toModel(ShortUrl $entity): ShortUrlModel
    {
        $model = new ShortUrlModel();
        if ($entity->id()) {
            $model->id = $entity->id();
        }
        $model->original_url = $entity->originalUrl();
        $model->short_code = $entity->shortCode();
        $model->clicks = $entity->clicks();
        return $model;
    }
}
