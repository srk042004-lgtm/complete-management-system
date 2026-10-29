<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('super_admins', 'role')) {
            Schema::table('super_admins', function (Blueprint $table): void {
                $table->string('role', 40)->default('super_admin');
            });
        }

        if (! Schema::hasColumn('super_admins', 'status')) {
            Schema::table('super_admins', function (Blueprint $table): void {
                $table->string('status', 20)->default('active');
            });
        }

        DB::table('super_admins')
            ->where('is_active', false)
            ->update(['status' => 'suspended']);
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['role', 'status'],
            fn (string $column): bool => Schema::hasColumn('super_admins', $column)
        ));

        if ($columns !== []) {
            Schema::table('super_admins', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
