<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ClickModel extends Model
{
    protected $fillable = [
        'short_url_id',
        'ip',
        'user_agent',
        'referer',
    ];
}
