<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'contact_number',
        'other_category',
        'category_id',
        'message'

    ];
}
