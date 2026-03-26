<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'uuid',
        'parent_id',
        'level',
        'title',
        'description',
        'cover_photo'
    ];
}
