<?php

namespace App\Support;

use App\Models\FoodCategory;
use App\Models\MenuItem;

class HotelExtraServiceCatalog
{
    private const SERVICES = [
        ['Laundry & Pressing', 'Shirt / Trouser Ironing', 'Standard shirt or trouser ironing', 150, 60],
        ['Laundry & Pressing', 'Suit Dry Cleaning (2-Piece)', 'Two-piece suit dry cleaning', 1200, 60],
        ['Laundry & Pressing', 'Washing & Folding (per KG)', 'Laundry washing and folding, per kilogram', 400, 60],
        ['Laundry & Pressing', 'Express Steam Pressing', 'Express steam pressing service', 300, 60],
        ['Minibar', 'Mineral Water (Large 1.5L)', 'Large 1.5 litre mineral water', 100, 70],
        ['Housekeeping', 'Extra Bed / Mattress', 'Extra bed or mattress setup', 1500, 80],
    ];

    private const CATEGORY_ALIASES = [
        'Laundry & Pressing' => ['laundry', 'press'],
        'Minibar' => ['minibar'],
        'Housekeeping' => ['housekeep', 'extra bed'],
    ];

    public function ensureForTenant(int $tenantId): void
    {
        $categories = [];
        foreach (self::SERVICES as [$categoryName, , , , $sortOrder]) {
            $aliases = self::CATEGORY_ALIASES[$categoryName];
            $category = FoodCategory::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($aliases): void {
                    foreach ($aliases as $alias) {
                        $query->orWhereRaw('LOWER(name) LIKE ?', ['%'.strtolower($alias).'%']);
                    }
                })
                ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$categoryName])
                ->first();
            $categories[$categoryName] = $category ?? FoodCategory::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $categoryName],
                ['icon' => 'service', 'sort_order' => $sortOrder],
            );
        }

        foreach (self::SERVICES as [$categoryName, $name, $description, $price]) {
            $existingItem = MenuItem::query()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])
                ->first();

            if ($existingItem) {
                continue;
            }

            MenuItem::query()->create([
                'tenant_id' => $tenantId,
                'food_category_id' => $categories[$categoryName]->id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'billing_type' => 'one_time_order',
                'is_available' => true,
            ]);
        }
    }
}
