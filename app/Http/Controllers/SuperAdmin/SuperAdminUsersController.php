<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\HotelRoleCatalog;
use App\Support\SuperAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class SuperAdminUsersController extends Controller
{
    /** Role filters map to every stored role that belongs to that workspace role (cashier is merged into receptionist). */
    private const ROLE_ALIASES = [
        'admin' => ['admin', 'hotel_admin'],
        'receptionist' => ['receptionist', 'cashier'],
        'housekeeping' => ['housekeeping', 'housekeeper'],
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'role' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
        ]);

        $users = $this->filteredUsers($filters)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.users', [
            'users' => $users,
            'tenants' => Tenant::query()->orderBy('hotel_name')->get(['id', 'hotel_name', 'tenant_name']),
            'filters' => $filters,
        ]);
    }

    /** Staff of one hotel, used by the hover preview in the directory. */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'integer', 'exists:tenants,id']]);

        $users = User::query()
            ->with('tenant:id,hotel_name,tenant_name')
            ->where('tenant_id', $validated['tenant_id'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'hotel_name' => $user->tenant?->hotel_name ?? $user->tenant?->tenant_name,
                'role_label' => HotelRoleCatalog::label((string) $user->role),
            ]);

        return response()->json(['users' => $users->values()]);
    }

    /** Toggles active/suspended when no explicit status is sent. */
    public function updateStatus(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(['status' => ['nullable', 'in:active,inactive,suspended']]);

        $status = $validated['status'] ?? ($user->status === 'suspended' ? 'active' : 'suspended');
        $user->update(['status' => $status]);
        SuperAdminActivity::log('user.status_changed', $user, "User {$user->email} status changed to {$user->status}.");

        if ($request->expectsJson()) {
            return response()->json(['status' => $user->status]);
        }

        return back()->with('success', 'User status updated.');
    }

    /** Sends the standard password reset email to the user. */
    public function sendPasswordReset(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $status = Password::broker('users')->sendResetLink(['email' => $user->email]);
        SuperAdminActivity::log('user.password_reset_sent', $user, "Password reset link sent to {$user->email}.");

        $sent = $status === Password::RESET_LINK_SENT;
        $message = $sent
            ? 'Password reset link sent to the user email address.'
            : __($status);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $sent ? 200 : 422);
        }

        return back()->with($sent ? 'success' : 'error', $message);
    }

    private function filteredUsers(array $filters): Builder
    {
        return User::query()
            ->with('tenant:id,hotel_name,tenant_name')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($filters['tenant_id'] ?? null, fn (Builder $query, int $tenantId) => $query->where('tenant_id', $tenantId))
            ->when($filters['role'] ?? null, function (Builder $query, string $role): void {
                $query->whereIn('role', self::ROLE_ALIASES[$role] ?? [$role]);
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));
    }
}
