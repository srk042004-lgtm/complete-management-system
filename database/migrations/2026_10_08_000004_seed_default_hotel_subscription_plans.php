<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            [
                'name' => 'Hotel PMS - Monthly',
                'description' => 'Monthly access to the ArtFlow hotel management system.',
                'billing_cycle' => 'monthly',
                'price' => 5000,
            ],
            [
                'name' => 'Hotel PMS - Annual',
                'description' => 'Annual access to the ArtFlow hotel management system, equivalent to two months free compared with monthly billing.',
                'billing_cycle' => 'yearly',
                'price' => 50000,
            ],
        ];

        foreach ($defaults as $plan) {
            $exists = DB::table('plans')
                ->where('is_active', true)
                ->where('billing_cycle', $plan['billing_cycle'])
                ->exists();

            if (! $exists) {
                DB::table('plans')->insert(array_merge($plan, [
                    'currency_code' => 'PKR',
                    'trial_days' => 0,
                    'has_rooms' => true,
                    'has_restaurant_pos' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        foreach (['Hotel PMS - Monthly', 'Hotel PMS - Annual'] as $name) {
            DB::table('plans')
                ->where('name', $name)
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('subscriptions')
                        ->whereColumn('subscriptions.plan_id', 'plans.id');
                })
                ->delete();
        }
    }
};
