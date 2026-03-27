<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'profile_photo',
        'rating',
        'review'
    ];
}
