<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasCommandHistory extends Model
{
    protected $fillable = [
        'session_token',
        'sequence',
        'command',
    ];
}
