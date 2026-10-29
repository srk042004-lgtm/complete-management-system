<?php

namespace App\Support;

final class HotelRoleCatalog
{
    /** @var array<string, array{label: string, feature: ?string, luxury_only?: bool}> */
    private const ROLES = [
        'owner' => ['label' => 'Hotel Owner / Executive', 'feature' => null],
        'admin' => ['label' => 'Hotel Manager / Admin', 'feature' => null],
        'manager' => ['label' => 'Manager / Operations Supervisor', 'feature' => null],
        'receptionist' => ['label' => 'Receptionist / Front Desk', 'feature' => 'reservations'],
        'housekeeping' => ['label' => 'Housekeeping Manager', 'feature' => 'housekeeping'],
        'accountant' => ['label' => 'Accountant / Night Auditor', 'feature' => 'finance'],
        'maintenance' => ['label' => 'Maintenance Engineer', 'feature' => 'maintenance'],
        'corporate' => ['label' => 'Multi-Branch Corporate Admin', 'feature' => 'reports', 'luxury_only' => true],
    ];

    public static function normalize(string $role): string
    {
        $normalized = strtolower(trim((string) $role));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;
        $normalized = trim($normalized, '_');

        return match ($normalized) {
            'hotel_admin', 'hotel-admin' => 'admin',
            'hotel_owner', 'hotel-owner' => 'owner',
            'hotel_manager', 'hotel-manager' => 'manager',
            'hotel_receptionist', 'hotel-receptionist' => 'receptionist',
            'housekeeper', 'housekeeping', 'housekeeping_staff', 'housekeeping-staff' => 'housekeeping',
            'cashier' => 'receptionist',
            default => $normalized,
        };
    }

    public static function label(string $role): string
    {
        $role = self::normalize($role);

        return self::ROLES[$role]['label'] ?? ucfirst(str_replace('_', ' ', $role));
    }

    public static function headerLabel(string $role): string
    {
        return match (self::normalize($role)) {
            'owner' => 'Owner Account',
            'admin' => 'Administrator',
            'manager' => 'Hotel Manager',
            'receptionist' => 'Receptionist & POS',
            'housekeeping' => 'Housekeeping Staff',
            default => self::label($role),
        };
    }

    public static function allows(string $role, array $modules, string $tier): bool
    {
        $role = self::normalize($role);
        $definition = self::ROLES[$role] ?? null;
        if ($definition === null) {
            return false;
        }

        if (($definition['luxury_only'] ?? false) && $tier !== 'LUXURY') {
            return false;
        }

        $feature = $definition['feature'];
        if ($feature === null) {
            return true;
        }

        return array_key_exists($feature, $modules)
            ? $modules[$feature] === true
            : in_array($feature, ['reservations', 'housekeeping', 'finance', 'rooms', 'maintenance'], true);
    }

    /** @return list<string> */
    public static function allowedCapabilities(string $role): array
    {
        return match (self::normalize($role)) {
            'owner' => ['dashboard.read', 'reservations.read', 'rooms.read', 'reports.read', 'finance.read', 'subscription.manage', 'staff.manage', 'audit.read', 'policy.manage'],
            'admin' => ['dashboard.read', 'frontdesk.dashboard', 'reservations.read', 'reservations.write', 'rooms.read', 'rooms.manage', 'inventory.manage', 'inventory.read', 'policy.manage', 'room.status', 'folio.manage', 'staff.manage', 'finance.write', 'pos.access', 'housekeeping.access', 'night-audit.run'],
            'manager' => ['dashboard.read', 'frontdesk.dashboard', 'reservations.read', 'reservations.write', 'room.status', 'folio.manage', 'housekeeping.access', 'reports.read'],
            'receptionist' => ['dashboard.read', 'frontdesk.dashboard', 'reservations.read', 'reservations.write', 'room.status', 'folio.manage', 'finance.write', 'pos.access', 'inventory.manage', 'inventory.read'],
            'housekeeping' => ['dashboard.read', 'housekeeping.access', 'room.status'],
            'accountant' => ['dashboard.read', 'reports.read', 'finance.read', 'finance.write'],
            'maintenance' => ['dashboard.read'],
            'corporate' => ['dashboard.read', 'reservations.read', 'reports.read', 'finance.read'],
            default => [],
        };
    }

    /** @return array<string, array{label: string, feature: ?string, luxury_only?: bool}> */
    public static function definitions(): array
    {
        return self::ROLES;
    }
}
