<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Casts\ExpirationDateCast;
use App\Infrastructure\Persistence\Eloquent\Casts\ShortCodeCast;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ShortUrlModel extends Model
{
    use HasUlids;

    protected $table = 'short_urls';
    protected $keyType = 'string';
    protected $casts = [
        'expires_at' => ExpirationDateCast::class,
        'short_code' => ShortCodeCast::class,
    ];
    protected $fillable = [
        'id',
        'original_url',
        'short_code',
        'clicks',
        'expires_at',
    ];
    protected $attributes = [
        'clicks' => 0,
    ];

}
