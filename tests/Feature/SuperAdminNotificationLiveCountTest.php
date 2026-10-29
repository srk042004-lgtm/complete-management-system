<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\SuperAdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminNotificationLiveCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_count_drops_by_one_each_time_a_notification_is_seen(): void
    {
        $admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'fname' => 'Platform',
            'email' => 'notify-admin@example.test',
            'number' => '03001234567',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
        ]);
        $notifications = collect(range(1, 5))->map(fn (int $i) => SuperAdminNotification::query()->create([
            'type' => 'test', 'title' => "Notice $i", 'message' => 'Body',
        ]));

        $this->actingAs($admin, 'super_admin')->getJson(route('super-admin.notifications.unread-count'))->assertOk()->assertExactJson(['count' => 5]);

        $this->actingAs($admin, 'super_admin')->patchJson(route('super-admin.notifications.seen', $notifications[0]))->assertOk()->assertJson(['count' => 4]);
        $this->actingAs($admin, 'super_admin')->patchJson(route('super-admin.notifications.seen', $notifications[1]))->assertOk()->assertJson(['count' => 3]);

        // Seeing the same one again does not reduce the count twice.
        $this->actingAs($admin, 'super_admin')->patchJson(route('super-admin.notifications.seen', $notifications[1]))->assertOk()->assertJson(['count' => 3]);
    }
}
