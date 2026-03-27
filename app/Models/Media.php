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
    
    public function meta()
    {
        return $this->hasMany(MediaMetaData::class, 'media_id');
    }

    public function parent()
    {
        return $this->belongsTo(Media::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Media::class, 'parent_id');
    }
}
