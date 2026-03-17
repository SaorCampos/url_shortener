<?php

namespace App\Domain\ShortUrl\ValueObjects;

class ShortCode
{
    private const LENGTH = 6;
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(
        private string $value
    ) {
        $this->validate($value);
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    private function validate(string $value): void
    {
        if (strlen($value) !== self::LENGTH) {
            throw new \InvalidArgumentException('ShortCode must be 6 characters');
        }
        if (!preg_match('/^[0-9a-zA-Z]+$/', $value)) {
            throw new \InvalidArgumentException('ShortCode must be base62');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
