@extends('super-admin.layouts.app')

@section('title', 'Notifications | Artflow')
@section('heading', 'Notifications')

@section('content')
    <div class="mb-5 flex justify-end">
        <form method="POST" action="{{ route('super-admin.notifications.read-all') }}">
            @csrf
            @method('PATCH')
            <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Mark all as read</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($notifications as $notification)
            <div id="notification-{{ $notification->id }}"
                 class="flex items-start justify-between gap-4 rounded-2xl border {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50' }} p-4"
                 @unless($notification->read_at) data-unread data-seen-url="{{ route('super-admin.notifications.seen', $notification) }}" @endunless>
                <div>
                    <div class="font-semibold">{{ $notification->title }}</div>
                    <div class="mt-1 text-sm text-slate-600">{{ $notification->message }}</div>
                    <div class="mt-2 text-xs text-slate-500">{{ $notification->created_at?->format('d M Y H:i') }}</div>
                </div>
                @if(!$notification->read_at)
                    <form method="POST" action="{{ route('super-admin.notifications.read', $notification) }}" data-mark-read>
                        @csrf
                        @method('PATCH')
                        <button class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">Mark read</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">No notifications available.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const timers = new Map();

            // A notification counts as seen once at least 60% of it has been on screen for 1.2 seconds.
            const markSeen = async (row) => {
                try {
                    const response = await fetch(row.dataset.seenUrl, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: '{}',
                    });
                    if (!response.ok) return;

                    const data = await response.json();
                    row.removeAttribute('data-unread');
                    row.classList.remove('border-blue-200', 'bg-blue-50');
                    row.classList.add('border-slate-200', 'bg-white');
                    row.querySelector('[data-mark-read]')?.remove();
                    window.saNotifications?.setCount(data.count);
                } catch (error) {
                    // Network problem: the row stays unread and will be retried when it is seen again.
                }
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    const row = entry.target;
                    if (entry.isIntersecting) {
                        timers.set(row, setTimeout(() => {
                            timers.delete(row);
                            observer.unobserve(row);
                            markSeen(row);
                        }, 1200));
                    } else if (timers.has(row)) {
                        clearTimeout(timers.get(row));
                        timers.delete(row);
                    }
                });
            }, { threshold: 0.6 });

            document.querySelectorAll('[data-unread]').forEach((row) => observer.observe(row));
        })();
    </script>
@endsection
