<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SuperAdmin extends Authenticatable
{
    use Notifiable;

    protected $table = 'super_admins';

    // Mirrors the column defaults so freshly created models carry the same role and status.
    protected $attributes = [
        'role' => 'super_admin',
        'status' => 'active',
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'fname',
        'email',
        'number',
        'password',
        'profile_picture',
        'cnic',
        'address',
        'is_active',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * @return array<string, string>
     */
    public static function teamRoles(): array
    {
        return [
            'super_admin' => 'Super Admin',
            'owner' => 'Owner',
            'finance_manager' => 'Finance Manager',
            'support_agent' => 'Support Agent',
            'developer' => 'Developer',
            'compliance_officer' => 'Compliance Officer',
        ];
    }
}
