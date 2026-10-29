<?php

namespace App\Models;

use App\Support\HotelFeatureCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'hotel_code',
        'slug',
        'tenant_name',
        'hotel_name',
        'owner_name',
        'owner_email',
        'email',
        'phone',
        'address',
        'city',
        'website',
        'notes',
        'property_type',
        'hotel_type',
        'description',
        'total_rooms',
        'state',
        'postal_code',
        'logo_path',
        'whatsapp',
        'business_name',
        'registration_number',
        'tax_number',
        'timezone',
        'date_format',
        'check_in_time',
        'check_out_time',
        'reception_phone',
        'reservation_phone',
        'contact_email',
        'emergency_contact',
        'contact_address',
        'country',
        'currency_code',
        'enabled_modules',
        'status',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'enabled_modules' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (empty($tenant->name)) {
                $tenant->name = $tenant->tenant_name ?? $tenant->hotel_name ?? $tenant->owner_name ?? 'Tenant';
            }

            if (empty($tenant->slug)) {
                $baseSlug = Str::slug($tenant->name) ?: 'tenant';
                $slug = $baseSlug;
                $suffix = 2;

                while (Tenant::query()->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$suffix}";
                    $suffix++;
                }

                $tenant->slug = $slug;
            }
        });
    }

    /**
     * Determine whether the tenant has an enabled module (e.g., 'restaurant_pos', 'rooms').
     */
    public function hasModule(string $moduleName): bool
    {
        return $this->hasFeature($moduleName);
    }

    /** @return array<string, bool> */
    public function moduleConfiguration(): array
    {
        return array_merge(
            HotelFeatureCatalog::defaults($this->hotel_type, $this->total_rooms),
            $this->enabled_modules ?? [],
        );
    }

    public function hasFeature(string $feature): bool
    {
        return ($this->moduleConfiguration()[$feature] ?? false) === true;
    }

    /**
     * Check if the tenant workspace is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function saasLedgers(): HasMany
    {
        return $this->hasMany(SaasLedger::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SaasPayment::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TenantSetting::class);
    }
}
