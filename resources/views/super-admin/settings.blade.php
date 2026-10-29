@extends('super-admin.layouts.app')

@section('title', 'Super Admin Settings | Artflow')
@section('heading', 'Super Admin Settings')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500">Platform-wide configuration and environment controls.</p>
        </div>
        <a href="{{ route('super-admin.dashboard') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('super-admin.settings.update') }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')
            <div><label class="mb-1 block text-sm font-semibold">Application name</label><input name="application_name" value="{{ old('application_name', data_get($settings->get('application_name'), 'value', 'Artflow Management System')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm font-semibold">Default currency</label><input name="default_currency" maxlength="3" value="{{ old('default_currency', data_get($settings->get('default_currency'), 'value', 'PKR')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm font-semibold">Support email</label><input name="support_email" type="email" value="{{ old('support_email', data_get($settings->get('support_email'), 'value')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm font-semibold">Support phone</label><input name="support_phone" value="{{ old('support_phone', data_get($settings->get('support_phone'), 'value')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm font-semibold">Trial duration (days)</label><input name="trial_duration_days" type="number" min="0" max="365" value="{{ old('trial_duration_days', data_get($settings->get('trial_duration_days'), 'value', config('saas.trial_duration_days', 14))) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <div class="flex items-center gap-6 pt-6 text-sm"><label><input type="checkbox" name="registration_enabled" value="1" @checked(old('registration_enabled', data_get($settings->get('registration_enabled'), 'value', true)))> Registration enabled</label><label><input type="checkbox" name="approval_required" value="1" @checked(old('approval_required', data_get($settings->get('approval_required'), 'value', false)))> Approval required</label></div>
            <div class="md:col-span-2 flex justify-end"><button class="rounded-xl bg-slate-950 px-5 py-2 text-sm font-semibold text-white">Save settings</button></div>
        </form>
    </div>
@endsection
