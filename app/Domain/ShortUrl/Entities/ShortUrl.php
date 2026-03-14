<?php

namespace App\Domain\ShortUrl\Entities;

class ShortUrl
{
    public function __construct(
        private ?int $id,
        private string $originalUrl,
        private string $shortCode,
        private int $clicks = 0
    ) {}

    public static function create(string $url, string $code): self
    {
        return new self(null, $url, $code, 0);
    }
    public static function restore(
        int $id,
        string $url,
        string $code,
        int $clicks
    ): self {
        return new self($id, $url, $code, $clicks);
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
    public function registerClick(): void
    {
        $this->clicks++;
    }
    public function withCode(string $code): self
    {
        return new self(
            $this->id,
            $this->originalUrl,
            $code,
            $this->clicks
        );
    }
}
