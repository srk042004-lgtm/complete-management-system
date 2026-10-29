<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::firstOrCreate(
            ['email' => 'superadmin@shifa.com'],
            [
                'name' => 'Shahrukh Khan',
                'fname' => 'Wilayat Shah',
                'number' => '03001234567',
                'password' => Hash::make('YourSecurePassword123!'),
                'address' => 'Nowshera, KPK, Pakistan',
                'is_active' => true,
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );
    }
}
