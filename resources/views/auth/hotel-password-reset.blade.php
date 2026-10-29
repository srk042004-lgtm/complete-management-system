<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Hotel Account Password | ArtFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.slate-teal-theme')
</head>
<body class="min-h-screen bg-slate-100 px-4 py-12 text-slate-900">
    <main class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">ArtFlow Hotel Management</p>
            <h1 class="mt-2 text-2xl font-semibold">Set a new password</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Choose a strong password with at least 12 characters for your hotel account.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="grid gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="grid gap-1.5 text-sm font-medium text-slate-700">
                Email address
                <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
            </label>
            <label class="grid gap-1.5 text-sm font-medium text-slate-700">
                New password
                <input type="password" name="password" required minlength="12" autocomplete="new-password" class="rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
            </label>
            <label class="grid gap-1.5 text-sm font-medium text-slate-700">
                Confirm new password
                <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-100">
            </label>
            <button type="submit" class="mt-2 rounded-lg bg-teal-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">Reset password</button>
        </form>
    </main>
</body>
</html>
