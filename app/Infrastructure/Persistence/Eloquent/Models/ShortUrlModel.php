<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrlModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';
    protected $table = 'short_urls';
    protected $fillable = [
        'original_url',
        'short_code',
        'clicks',
        'expires_at',
    ];

}
