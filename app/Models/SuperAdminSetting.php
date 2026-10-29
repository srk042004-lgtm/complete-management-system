<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
