@extends('super-admin.layouts.app')

@section('title', 'Activity Logs | Artflow')
@section('heading', 'Activity Logs')

@section('content')
    <form method="GET" class="mb-5 flex gap-3 rounded-2xl border border-slate-200 bg-white p-4"><input name="search" value="{{ $search }}" placeholder="Search actions or descriptions" class="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm"><button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Search logs</button></form>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Time</th><th class="px-4 py-3">Admin</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">IP</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($logs as $log)<tr><td class="px-4 py-3">{{ $log->created_at?->format('d M Y H:i') }}</td><td class="px-4 py-3">{{ $log->superAdmin?->name ?? 'System' }}</td><td class="px-4 py-3 font-semibold">{{ $log->action }}</td><td class="px-4 py-3">{{ $log->description }}</td><td class="px-4 py-3">{{ $log->ip_address ?? '—' }}</td></tr>@empty<tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No activity recorded.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
