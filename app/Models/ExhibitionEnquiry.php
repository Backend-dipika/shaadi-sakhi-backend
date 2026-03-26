<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExhibitionEnquiry extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'brand_name',
        'email',
        'contact_number',
        'category_id',
        'social_media',
    ];
}
