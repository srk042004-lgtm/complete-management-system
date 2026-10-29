<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Roles evolve with the hotel workflow; an ENUM rejects valid owner/admin registrations.
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(32) NOT NULL DEFAULT 'receptionist'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Preserve every role currently used by the application if this migration is rolled back.
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'hotel_admin', 'owner', 'admin', 'receptionist', 'housekeeper', 'housekeeping', 'cashier', 'accountant', 'corporate') NOT NULL DEFAULT 'receptionist'");
        }
    }
};
