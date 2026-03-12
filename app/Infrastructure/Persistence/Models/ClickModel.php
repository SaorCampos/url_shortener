<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Database\Eloquent\Model;

class ClickModel extends Model
{
    protected $fillable = [
        'short_url_id',
        'ip',
        'user_agent',
        'referer',
    ];

    public function shortUrl()
    {
        return $this->belongsTo(ShortUrlModel::class);
    }
}
