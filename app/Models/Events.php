<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    protected $fillable = [
        'uuid',
        'title',
        'description',
        'venue',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function metadata()
    {
        return $this->hasMany(EventMetadata::class, 'event_id');
    }
}
