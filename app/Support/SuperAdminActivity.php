<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\SuperAdmin;
use App\Models\SuperAdminNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SuperAdminActivity
{
    public static function log(string $action, ?Model $target, string $description): ActivityLog
    {
        $admin = Auth::guard('super_admin')->user();

        return ActivityLog::query()->create([
            'super_admin_id' => $admin?->id,
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    public static function notify(string $type, string $title, string $message, ?Model $target = null): SuperAdminNotification
    {
        return SuperAdminNotification::query()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
        ]);
    }
}
