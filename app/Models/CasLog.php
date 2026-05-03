<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasLog extends Model
{
    protected $fillable = [
        'source',
        'command',
        'success',
        'output',
        'error_message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];
}
