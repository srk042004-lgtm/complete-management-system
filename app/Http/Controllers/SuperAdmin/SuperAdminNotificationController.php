<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SuperAdminNotificationController extends Controller
{
    public function index(): View
    {
        $notifications = SuperAdminNotification::query()->latest('id')->paginate(25);

        return view('super-admin.notifications', compact('notifications'));
    }

    public function markRead(SuperAdminNotification $notification): RedirectResponse
    {
        $notification->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        SuperAdminNotification::query()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    /** Live badge: how many notifications nobody has seen yet. */
    public function unreadCount(): JsonResponse
    {
        return response()->json(['count' => $this->unreadTotal()]);
    }

    /** Called when a notification has been on screen long enough to count as seen. */
    public function seen(SuperAdminNotification $notification): JsonResponse
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['id' => $notification->id, 'count' => $this->unreadTotal()]);
    }

    private function unreadTotal(): int
    {
        return SuperAdminNotification::query()->whereNull('read_at')->count();
    }
}
