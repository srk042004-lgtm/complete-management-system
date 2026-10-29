<?php

namespace App\Support;

final class HotelFeatureCatalog
{
    /** @var array<string, array<string, string>> */
    private const CONFIGURABLE = [
        'accommodation' => [
            'rooms' => 'Rooms',
            'reservations' => 'Reservations',
            'front_desk' => 'Front Desk & Check-in/out',
            'guest_management' => 'Guest Management',
        ],
        'housekeeping' => [
            'housekeeping' => 'Housekeeping & Room Cleaning',
        ],
        'food_beverage' => [
            'restaurant' => 'Restaurant',
            'pos' => 'Restaurant POS',
        ],
        'operations' => [
            'staff' => 'Staff Management',
            'finance' => 'Finance & Payments',
            'reports' => 'Reports',
            'maintenance' => 'Maintenance Access',
        ],
    ];

    /** @var array<string, array<string, string>> */
    private const COMING_SOON = [
        'housekeeping' => [
            'laundry' => 'Laundry & Linen Management',
            'lost_found' => 'Lost & Found',
            'minibar' => 'Minibar Management',
        ],
        'food_beverage' => [
            'cafe' => 'Café & Bar Outlets',
            'room_service' => 'Room Service & In-room Dining',
            'kitchen_orders' => 'Kitchen Order Management',
        ],
        'events' => [
            'events' => 'Events & Banquets',
            'weddings' => 'Wedding Management',
            'conferences' => 'Conference & Meeting Rooms',
        ],
        'wellness' => [
            'spa' => 'Spa & Massage',
            'gym' => 'Fitness Center & Gym',
            'sauna' => 'Sauna & Steam Room',
        ],
        'recreation' => [
            'pool' => 'Swimming Pool',
            'sports' => 'Sports Facilities',
            'kids_club' => 'Kids Club & Game Room',
        ],
        'guest_services' => [
            'concierge' => 'Concierge & Bell Desk',
            'airport_transfer' => 'Airport Transfer',
            'valet_parking' => 'Valet Parking',
        ],
        'maintenance' => [
            'maintenance' => 'Maintenance & Engineering',
            'preventive_maintenance' => 'Preventive Maintenance',
            'fire_safety' => 'Fire Safety & Equipment',
        ],
        'finance_inventory_analytics' => [
            'inventory' => 'Inventory & Suppliers',
            'purchase_orders' => 'Purchase Orders & Stock Transfers',
            'advanced_analytics' => 'Advanced Analytics & Forecasting',
            'revenue_management' => 'Revenue Management & CRM',
            'guest_loyalty' => 'Guest Loyalty & Memberships',
            'multi_outlet' => 'Multiple Outlets & Buildings',
        ],
    ];

    /** @return array<string, array<string, string>> */
    public static function configurable(): array
    {
        return self::CONFIGURABLE;
    }

    /** @return array<string, array<string, string>> */
    public static function comingSoon(): array
    {
        return self::COMING_SOON;
    }

    /** @return list<string> */
    public static function configurableKeys(): array
    {
        $keys = [];
        foreach (self::CONFIGURABLE as $features) {
            $keys = array_merge($keys, array_keys($features));
        }

        return $keys;
    }

    /** @return list<string> */
    public static function optionalKeys(): array
    {
        return ['housekeeping', 'restaurant', 'pos', 'staff', 'finance', 'reports', 'maintenance'];
    }

    /** @return array<string, bool> */
    public static function defaults(?string $hotelType, ?int $roomCount = null): array
    {
        $type = self::normalizeType($hotelType, $roomCount);
        $restaurantDefault = in_array($type, ['luxury', 'large'], true);

        return [
            'rooms' => true,
            'reservations' => true,
            'front_desk' => true,
            'guest_management' => true,
            'housekeeping' => true,
            'restaurant' => $restaurantDefault,
            'pos' => $restaurantDefault,
            'staff' => true,
            'finance' => true,
            'reports' => true,
            'maintenance' => true,
        ];
    }

    public static function normalizeType(?string $hotelType, ?int $roomCount = null): string
    {
        $value = strtolower(trim((string) $hotelType));

        if (str_contains($value, 'luxury') || str_contains($value, 'five') || str_contains($value, '5-star') || str_contains($value, 'fivestar')) {
            return 'luxury';
        }

        if (str_contains($value, 'large') || str_contains($value, 'full-service') || str_contains($value, 'full service')) {
            return 'large';
        }

        if (str_contains($value, 'small') || str_contains($value, 'boutique') || str_contains($value, 'budget')) {
            return 'small';
        }

        return ($roomCount ?? 0) > 60 ? 'large' : 'small';
    }

    public static function operatingTier(?string $hotelType, ?int $roomCount = null): string
    {
        return self::normalizeType($hotelType, $roomCount) === 'luxury' ? 'LUXURY' : 'STANDARD';
    }
}
