<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaMetaData extends Model
{
    protected $fillable = [
        'media_id',
        'type',
        'path',
    ];
}
