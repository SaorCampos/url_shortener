<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ClickModel extends Model
{
    use HasUlids;

    protected $table = 'clicks';
    protected $fillable = [
        'id',
        'short_url_id',
        'ip',
        'country_code',
        'user_agent',
        'referer',
        'lat',
        'lng',
    ];

    public function shortUrls()
    {
        return $this->belongsTo(ShortUrlModel::class, 'short_url_id');
    }
}
