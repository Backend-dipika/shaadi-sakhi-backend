<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventMetaData extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'path',
    ];
}
