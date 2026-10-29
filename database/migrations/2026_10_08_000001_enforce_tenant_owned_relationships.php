<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillLegacyRoomTenants();
        $this->preflightLegacyTenantRows();

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id');
        });
        Schema::table('booking_rooms', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id');
        });
        Schema::table('booking_food_packages', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id');
        });
        Schema::table('folios', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id');
        });
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id');
        });

        $this->backfillTenantId('menu_items', 'food_categories', 'food_category_id');
        $this->backfillTenantId('booking_rooms', 'bookings', 'booking_id');
        $this->backfillTenantId('booking_food_packages', 'bookings', 'booking_id');
        $this->backfillTenantId('reservations', 'rooms', 'room_id');
        $this->backfillTenantId('folios', 'bookings', 'booking_id', true);
        $this->backfillTenantId('folios', 'reservations', 'reservation_id', true);

        if (DB::table('reservations')->whereNull('tenant_id')->exists()) {
            throw new RuntimeException('Reservations without an owning hotel room exist; assign each reservation to a tenant-owned room before applying this migration.');
        }
        if (DB::table('folios')->whereNull('tenant_id')->exists()) {
            throw new RuntimeException('Folio entries without a tenant-owned booking or reservation exist; assign each entry to its owning hotel before applying this migration.');
        }

        foreach ([
            ['rooms', 'room_types', 'room_type_id'],
            ['bookings', 'guests', 'guest_id'],
            ['reservations', 'rooms', 'room_id'],
            ['menu_items', 'food_categories', 'food_category_id'],
            ['booking_rooms', 'bookings', 'booking_id'],
            ['booking_rooms', 'rooms', 'room_id'],
            ['booking_food_packages', 'bookings', 'booking_id'],
            ['booking_food_packages', 'menu_items', 'menu_item_id'],
            ['booking_food_packages', 'food_categories', 'food_category_id'],
            ['hotel_payments', 'bookings', 'booking_id'],
            ['hotel_payments', 'users', 'recorded_by'],
            ['hotel_operation_logs', 'users', 'actor_id'],
            ['folios', 'bookings', 'booking_id'],
            ['folios', 'reservations', 'reservation_id'],
        ] as [$child, $parent, $foreignKey]) {
            $this->assertTenantOwnership($child, $parent, $foreignKey);
        }

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
        Schema::table('booking_rooms', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
        Schema::table('booking_food_packages', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
        Schema::table('room_types', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
        Schema::table('folios', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_users_tenant_id_id');
        });
        Schema::table('room_types', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_room_types_tenant_id_id');
        });
        Schema::table('rooms', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_rooms_tenant_id_id');
            $table->unique(['tenant_id', 'room_number'], 'uq_rooms_tenant_room_number');
        });
        Schema::table('guests', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_guests_tenant_id_id');
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_bookings_tenant_id_id');
        });
        Schema::table('reservations', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_reservations_tenant_id_id');
        });
        Schema::table('food_categories', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_food_categories_tenant_id_id');
        });
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'uq_menu_items_tenant_id_id');
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreign(['tenant_id', 'room_type_id'], 'fk_rooms_tenant_category')
                ->references(['tenant_id', 'id'])->on('room_types');
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign(['tenant_id', 'guest_id'], 'fk_bookings_tenant_guest')
                ->references(['tenant_id', 'id'])->on('guests');
        });
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreign('tenant_id', 'fk_reservations_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'room_id'], 'fk_reservations_tenant_room')
                ->references(['tenant_id', 'id'])->on('rooms')->restrictOnDelete();
        });
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->foreign(['tenant_id', 'food_category_id'], 'fk_menu_items_tenant_category')
                ->references(['tenant_id', 'id'])->on('food_categories');
            $table->foreign('tenant_id', 'fk_menu_items_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
        });
        Schema::table('booking_rooms', function (Blueprint $table): void {
            $table->foreign('tenant_id', 'fk_booking_rooms_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'booking_id'], 'fk_booking_rooms_tenant_booking')
                ->references(['tenant_id', 'id'])->on('bookings')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'room_id'], 'fk_booking_rooms_tenant_room')
                ->references(['tenant_id', 'id'])->on('rooms')->restrictOnDelete();
        });
        Schema::table('booking_food_packages', function (Blueprint $table): void {
            $table->foreign('tenant_id', 'fk_booking_food_packages_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'booking_id'], 'fk_booking_food_packages_tenant_booking')
                ->references(['tenant_id', 'id'])->on('bookings')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'menu_item_id'], 'fk_booking_food_packages_tenant_item')
                ->references(['tenant_id', 'id'])->on('menu_items')->restrictOnDelete();
            $table->foreign(['tenant_id', 'food_category_id'], 'fk_booking_food_packages_tenant_category')
                ->references(['tenant_id', 'id'])->on('food_categories')->restrictOnDelete();
        });
        Schema::table('hotel_payments', function (Blueprint $table): void {
            $table->foreign(['tenant_id', 'booking_id'], 'fk_hotel_payments_tenant_booking')
                ->references(['tenant_id', 'id'])->on('bookings')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'recorded_by'], 'fk_hotel_payments_tenant_user')
                ->references(['tenant_id', 'id'])->on('users')->restrictOnDelete();
        });
        Schema::table('hotel_operation_logs', function (Blueprint $table): void {
            $table->foreign(['tenant_id', 'actor_id'], 'fk_hotel_operation_logs_tenant_actor')
                ->references(['tenant_id', 'id'])->on('users')->restrictOnDelete();
        });
        Schema::table('folios', function (Blueprint $table): void {
            $table->foreign('tenant_id', 'fk_folios_tenant')
                ->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'booking_id'], 'fk_folios_tenant_booking')
                ->references(['tenant_id', 'id'])->on('bookings')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'reservation_id'], 'fk_folios_tenant_reservation')
                ->references(['tenant_id', 'id'])->on('reservations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('folios', function (Blueprint $table): void {
            $table->dropForeign('fk_folios_tenant_reservation');
            $table->dropForeign('fk_folios_tenant_booking');
            $table->dropForeign('fk_folios_tenant');
        });
        Schema::table('hotel_operation_logs', function (Blueprint $table): void {
            $table->dropForeign('fk_hotel_operation_logs_tenant_actor');
        });
        Schema::table('hotel_payments', function (Blueprint $table): void {
            $table->dropForeign('fk_hotel_payments_tenant_user');
            $table->dropForeign('fk_hotel_payments_tenant_booking');
        });
        Schema::table('booking_food_packages', function (Blueprint $table): void {
            $table->dropForeign('fk_booking_food_packages_tenant_category');
            $table->dropForeign('fk_booking_food_packages_tenant_item');
            $table->dropForeign('fk_booking_food_packages_tenant_booking');
            $table->dropForeign('fk_booking_food_packages_tenant');
        });
        Schema::table('booking_rooms', function (Blueprint $table): void {
            $table->dropForeign('fk_booking_rooms_tenant_room');
            $table->dropForeign('fk_booking_rooms_tenant_booking');
            $table->dropForeign('fk_booking_rooms_tenant');
        });
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropForeign('fk_menu_items_tenant');
            $table->dropForeign('fk_menu_items_tenant_category');
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropForeign('fk_bookings_tenant_guest');
        });
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropForeign('fk_reservations_tenant_room');
            $table->dropForeign('fk_reservations_tenant');
        });
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropForeign('fk_rooms_tenant_category');
        });
        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->change();
        });
        Schema::table('room_types', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->change();
        });

        foreach ([
            ['menu_items', 'uq_menu_items_tenant_id_id'],
            ['food_categories', 'uq_food_categories_tenant_id_id'],
            ['bookings', 'uq_bookings_tenant_id_id'],
            ['reservations', 'uq_reservations_tenant_id_id'],
            ['guests', 'uq_guests_tenant_id_id'],
            ['rooms', 'uq_rooms_tenant_id_id'],
            ['rooms', 'uq_rooms_tenant_room_number'],
            ['room_types', 'uq_room_types_tenant_id_id'],
            ['users', 'uq_users_tenant_id_id'],
        ] as [$tableName, $indexName]) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropUnique($indexName));
        }

        foreach ([
            ['reservations', 'tenant_id'],
            ['folios', 'tenant_id'],
            ['booking_food_packages', 'tenant_id'],
            ['booking_rooms', 'tenant_id'],
            ['menu_items', 'tenant_id'],
        ] as [$tableName, $column]) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($column));
        }
    }

    private function backfillTenantId(string $childTable, string $parentTable, string $foreignKey, bool $onlyMissing = false): void
    {
        $query = DB::table($childTable)
            ->join($parentTable, "{$parentTable}.id", '=', "{$childTable}.{$foreignKey}")
            ->select("{$childTable}.id as tenant_backfill_id", "{$parentTable}.tenant_id as tenant_id")
            ->orderBy("{$childTable}.id");

        if ($onlyMissing) {
            $query->whereNull("{$childTable}.tenant_id");
        }

        $query->chunkById(500, function ($rows) use ($childTable): void {
            foreach ($rows as $row) {
                DB::table($childTable)->where('id', $row->tenant_backfill_id)->update(['tenant_id' => $row->tenant_id]);
            }
        }, "{$childTable}.id", 'tenant_backfill_id');
    }

    private function backfillLegacyRoomTenants(): void
    {
        foreach (DB::table('room_types')->whereNull('tenant_id')->orderBy('id')->get(['id']) as $roomType) {
            $tenantIds = DB::table('rooms')
                ->where('room_type_id', $roomType->id)
                ->whereNotNull('tenant_id')
                ->distinct()
                ->pluck('tenant_id');

            if ($tenantIds->count() !== 1) {
                throw new RuntimeException("Room category {$roomType->id} has no single tenant owner; assign it to exactly one hotel before applying this migration.");
            }

            DB::table('room_types')->where('id', $roomType->id)->update(['tenant_id' => $tenantIds->first()]);
        }

        foreach (DB::table('rooms')->whereNull('tenant_id')->orderBy('id')->get(['id', 'room_type_id']) as $room) {
            $tenantId = $room->room_type_id === null
                ? null
                : DB::table('room_types')->where('id', $room->room_type_id)->value('tenant_id');

            if ($tenantId === null) {
                throw new RuntimeException("Room {$room->id} has no tenant owner; assign it to a hotel before applying this migration.");
            }

            DB::table('rooms')->where('id', $room->id)->update(['tenant_id' => $tenantId]);
        }
    }

    private function preflightLegacyTenantRows(): void
    {
        $unownedReservations = DB::table('reservations')
            ->leftJoin('rooms', 'rooms.id', '=', 'reservations.room_id')
            ->whereNull('rooms.tenant_id')
            ->exists();
        if ($unownedReservations) {
            throw new RuntimeException('A reservation references a room without a tenant owner; resolve the room ownership before applying this migration.');
        }

        $unownedFolios = DB::table('folios')
            ->leftJoin('bookings', 'bookings.id', '=', 'folios.booking_id')
            ->leftJoin('reservations', 'reservations.id', '=', 'folios.reservation_id')
            ->leftJoin('rooms as reservation_rooms', 'reservation_rooms.id', '=', 'reservations.room_id')
            ->whereNull('bookings.tenant_id')
            ->whereNull('reservation_rooms.tenant_id')
            ->exists();
        if ($unownedFolios) {
            throw new RuntimeException('A folio entry has no tenant-owned booking or reservation; resolve its ownership before applying this migration.');
        }
    }

    private function assertTenantOwnership(string $childTable, string $parentTable, string $foreignKey): void
    {
        $mismatch = DB::table($childTable)
            ->join($parentTable, "{$parentTable}.id", '=', "{$childTable}.{$foreignKey}")
            ->whereNotNull("{$childTable}.{$foreignKey}")
            ->where(function ($query) use ($childTable, $parentTable): void {
                $query->whereNull("{$parentTable}.tenant_id")
                    ->orWhereColumn("{$childTable}.tenant_id", '<>', "{$parentTable}.tenant_id");
            })
            ->exists();

        if ($mismatch) {
            throw new RuntimeException("Tenant ownership mismatch found between {$childTable}.{$foreignKey} and {$parentTable}.id; resolve the affected records before applying this migration.");
        }
    }
};
