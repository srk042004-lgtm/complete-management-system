<?php

namespace App\Support;

/**
 * Every room cleanliness state the housekeeping ecosystem uses, with its label and badge colour.
 * `clean` is the verified, available state the front desk sees; older keys remain valid so existing data keeps working.
 */
final class HousekeepingStatus
{
    /** @return array<string, array{label: string, pill: string}> */
    public static function all(): array
    {
        return [
            'dirty' => ['label' => 'Dirty', 'pill' => 'pill--dirty'],
            'cleaning' => ['label' => 'Cleaning', 'pill' => 'pill--cleaning'],
            'clean_uninspected' => ['label' => 'Awaiting inspection', 'pill' => 'pill--inspect'],
            'inspection_pending' => ['label' => 'Inspecting', 'pill' => 'pill--inspect'],
            'clean' => ['label' => 'Clean · Available', 'pill' => 'pill--clean'],
            'touch_up_needed' => ['label' => 'Touch-up needed', 'pill' => 'pill--maint'],
            'maintenance' => ['label' => 'Out of service', 'pill' => 'pill--service'],
            'out_of_order' => ['label' => 'Out of order', 'pill' => 'pill--service'],
            'do_not_disturb' => ['label' => 'Do not disturb', 'pill' => 'pill--neutral'],
        ];
    }

    /** Statuses a housekeeper may set. Admins can also verify and release rooms. */
    public static function allowedFor(string $role): array
    {
        $housekeeper = ['dirty', 'cleaning', 'clean_uninspected', 'touch_up_needed', 'maintenance', 'do_not_disturb'];

        return match ($role) {
            'admin' => array_keys(self::all()),
            'housekeeping' => $housekeeper,
            default => [],
        };
    }

    public static function label(?string $status): string
    {
        return self::all()[$status]['label'] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    public static function pill(?string $status): string
    {
        return self::all()[$status]['pill'] ?? 'pill--neutral';
    }
}
