<?php

namespace App\Domain\ShortUrl\Entities;

use DateTimeImmutable;

class ShortUrl
{
    public function __construct(
        private ?int $id,
        private string $originalUrl,
        private string $shortCode,
        private int $clicks = 0,
        private ?DateTimeImmutable $expiresAt = null
    ) {}

    public static function create(
        int $id,
        string $url,
        string $code,
        ?DateTimeImmutable $expiresAt = null
    ): self {
        return new self($id, $url, $code, 0, $expiresAt);
    }
    public static function restore(
        int $id,
        string $url,
        string $code,
        int $clicks,
        ?DateTimeImmutable $expiresAt
    ): self {
        return new self($id, $url, $code, $clicks, $expiresAt);
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
        return $this->shortCode;
    }
    public function clicks(): int
    {
        return $this->clicks;
    }
    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }
    public function isExpired(): bool
    {
        return $this->expiresAt !== null &&
            new DateTimeImmutable() > $this->expiresAt;
    }
    public function registerClick(): void
    {
        $this->clicks++;
    }
}
