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

    // Vytvori stabilny hash IP adresy, aby sa do logov neukladala citatelna IP.
    // Pouziva sa v controlleroch pri vytvarani zaznamov v tabulke cas_logs.
    public static function hashIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        return hash_hmac('sha256', $ipAddress, (string) config('app.key'));
    }
}
