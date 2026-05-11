<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimationUsage extends Model
{
    protected $fillable = [
        'animation_type',
        'visitor_token',
        'ip_hash',
        'city',
        'country',
    ];
}
