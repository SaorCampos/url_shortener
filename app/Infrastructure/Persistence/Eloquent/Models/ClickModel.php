<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ClickFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClickModel extends Model
{
    use HasUlids, HasFactory;

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

    protected static function newFactory()
    {
        return ClickFactory::new();
    }
}
