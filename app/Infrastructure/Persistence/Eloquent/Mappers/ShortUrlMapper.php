<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\ShortUrl\DTO\ShortUrlData;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\ValueObjects\ExpirationDate;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use DateTimeImmutable;

class ShortUrlMapper
{
    public static function toEntity(ShortUrlModel $model): ShortUrl
    {
        $expiresAt = $model->expires_at;
        if ($expiresAt instanceof DateTimeImmutable) {
            $expiresAt = ExpirationDate::from($expiresAt);
        }
        return ShortUrl::restore(
            new ShortUrlData(
                id: (string)$model->id,
                originalUrl: $model->original_url,
                shortCode: $model->short_code->value(),
                clicks: (int)($model->clicks ?? 0),
                expiresAt: $expiresAt
            )
        );
    }

    public static function toModel(ShortUrl $entity, ?ShortUrlModel $model = null): ShortUrlModel
    {
        $model = $model ?? new ShortUrlModel();
        if ($entity->id()) {
            $model->id = $entity->id();
        }
        $model->original_url = $entity->originalUrl();
        $model->short_code = $entity->shortCode();
        $model->clicks = $entity->clicks();
        $model->expires_at = $entity->expiresAt();
        return $model;
    }
}
