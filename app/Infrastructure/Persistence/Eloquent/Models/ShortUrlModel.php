<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Casts\ExpirationDateCast;
use App\Infrastructure\Persistence\Eloquent\Casts\ShortCodeCast;
use Illuminate\Database\Eloquent\Model;

class ShortUrlModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';
    protected $table = 'short_urls';
    protected $casts = [
        'expires_at' => ExpirationDateCast::class,
        'short_code' => ShortCodeCast::class,
    ];
    protected $fillable = [
        'original_url',
        'short_code',
        'clicks',
        'expires_at',
    ];

}
