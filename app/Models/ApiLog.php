<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'user_id','service','endpoint','method','status','duration_ms','request','response'
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
    ];
}
