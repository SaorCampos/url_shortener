<?php

namespace App\Domain\ShortUrl\Entities;

use App\Domain\ShortUrl\DTO\ShortUrlData;
use App\Domain\ShortUrl\ValueObjects\ExpirationDate;
use App\Domain\ShortUrl\ValueObjects\ShortCode;
use DateTimeImmutable;

class ShortUrl
{
    public function __construct(
        private ?int $id,
        private string $originalUrl,
        private ShortCode $shortCode,
        private int $clicks = 0,
        private ?ExpirationDate $expiresAt = null
    ) {}

    public static function create(
        int $id,
        string $url,
        string $code,
        ?ExpirationDate $expiresAt = null
    ): self {
        if (!$expiresAt) {
            $days = config('shorturl.default_expiration_days');
            $expiresAt = ExpirationDate::from(
                new \DateTimeImmutable("+{$days} days")
            );
        }
        return new self(
            $id,
            $url,
            ShortCode::from($code),
            0,
            $expiresAt
        );
    }
    public static function restore(ShortUrlData $data): self
    {
        return new self(
            $data->id,
            $data->originalUrl,
            ShortCode::from($data->shortCode),
            $data->clicks,
            $data->expiresAt
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }
    public function originalUrl(): string
    {
        return $this->originalUrl;
    }
    public function shortCode(): string
    {
        return $this->shortCode->value();
    }
    public function clicks(): int
    {
        return $this->clicks;
    }
    public function isExpired(): bool
    {
        return $this->expiresAt?->isExpired() ?? false;
    }
    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt?->value();
    }
    public function registerClick(): void
    {
        $this->clicks++;
    }
}
