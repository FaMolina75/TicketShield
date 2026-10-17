<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'event',
        'ip_address',
        'user_agent',
    ];
}