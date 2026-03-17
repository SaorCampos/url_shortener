<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\ShortUrl\DTO\ShortUrlData;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;

class ShortUrlMapper
{
    public static function toEntity(ShortUrlModel $model): ShortUrl
    {
        return ShortUrl::restore(
            new ShortUrlData(
                id: $model->id,
                originalUrl: $model->original_url,
                shortCode: $model->short_code->value(),
                clicks: $model->clicks,
                expiresAt: $model->expires_at
            )
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
        $model->expires_at = $entity->expiresAt()?->format('Y-m-d H:i:s');
        return $model;
    }
}
