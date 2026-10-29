<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('room_types', 'adults')) {
                $table->unsignedInteger('adults')->default(2);
            }

            if (! Schema::hasColumn('room_types', 'children')) {
                $table->unsignedInteger('children')->default(0);
            }
        });

        Schema::table('guests', function (Blueprint $table): void {
            if (! Schema::hasColumn('guests', 'opening_balance')) {
                $table->decimal('opening_balance', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('guests', 'photo_path')) {
                $table->string('photo_path')->nullable();
            }

            if (! Schema::hasColumn('guests', 'id_document_path')) {
                $table->string('id_document_path')->nullable();
            }
        });

        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'adults')) {
                $table->unsignedInteger('adults')->default(1);
            }

            if (! Schema::hasColumn('bookings', 'children')) {
                $table->unsignedInteger('children')->default(0);
            }
        });
    }

    public function down(): void
    {
        foreach ([
            'bookings' => ['adults', 'children'],
            'guests' => ['opening_balance', 'photo_path', 'id_document_path'],
            'room_types' => ['adults', 'children'],
        ] as $tableName => $columns) {
            $existingColumns = array_filter($columns, fn (string $column): bool => Schema::hasColumn($tableName, $column));
            if ($existingColumns !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($existingColumns): void {
                    $table->dropColumn(array_values($existingColumns));
                });
            }
        }
    }
};
