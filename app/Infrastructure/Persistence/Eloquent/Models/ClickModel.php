<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ClickModel extends Model
{
    use HasUlids;

    protected $fillable = [
        'id',
        'short_url_id',
        'ip',
        'user_agent',
        'referer',
    ];

    public function shortUrl()
    {
        return $this->belongsTo(ShortUrlModel::class, 'short_url_id');
    }
}
