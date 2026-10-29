<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.16), transparent 20%),
                linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-5xl bg-white shadow-2xl rounded-3xl overflow-hidden border border-slate-200/80">
        <div class="grid md:grid-cols-[1fr_1.2fr]">
            <div class="bg-slate-950 text-white p-8 md:p-10">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em]">Secure Registration</div>
                <h1 class="mt-6 text-3xl font-bold leading-tight">Create Your Super Admin Workspace</h1>
                <p class="mt-4 text-slate-300 text-sm leading-6">
                    Set up a protected master account with stronger security controls, platform presets, and clear access governance.
                </p>
                <div class="mt-8 space-y-3">
                    <div class="rounded-2xl bg-white/10 p-4">
                        <div class="text-sm font-semibold">Zero-tolerance security</div>
                        <div class="text-xs text-slate-300 mt-1">Strong password rules, PIN control, and role-level protection.</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4">
                        <div class="text-sm font-semibold">Single master access</div>
                        <div class="text-xs text-slate-300 mt-1">Designed for a permanent super admin account with controlled tenant setup.</div>
                    </div>
                </div>
            </div>

            <div class="p-8 md:p-10 bg-slate-50">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <div class="font-semibold mb-2">Please correct the following:</div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('super-admin.register.store') }}" class="space-y-6">
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-slate-900">Section 1: Profile Details</h2>
                            <p class="text-sm text-slate-500">Enter the basic profile metadata for the super admin account.</p>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="Shahrukh" required>
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
                                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="Khan" required>
                            </div>
                            <div>
                                <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                                <input id="username" name="username" type="text" value="{{ old('username') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="superadmin" required>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email', 'superadmin@shifa.com') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="superadmin@shifa.com" required>
                            </div>
                            <div class="md:col-span-2">
                                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="03001234567" required>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-slate-900">Section 2: Security Guards</h2>
                            <p class="text-sm text-slate-500">Use a secure password, confirm it, and enter your 6-digit authorization PIN.</p>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                                <div class="relative">
                                    <input id="password" name="password" type="password" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 pr-12 focus:border-blue-500 focus:outline-none" placeholder="Strong password required" required>
                                    <button type="button" data-toggle-target="password" class="absolute right-2 top-2 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Show</button>
                                </div>
                                <div class="mt-3">
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                        <span>Password strength</span>
                                        <span id="passwordStrengthLabel">Weak</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                        <div id="passwordStrengthBar" class="h-full w-0 rounded-full bg-red-500 transition-all duration-300"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                                <div class="relative">
                                    <input id="password_confirmation" name="password_confirmation" type="password" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 pr-12 focus:border-blue-500 focus:outline-none" placeholder="Re-enter password" required>
                                    <button type="button" data-toggle-target="password_confirmation" class="absolute right-2 top-2 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Show</button>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label for="authorization_pin" class="block text-sm font-medium text-slate-700 mb-1">Secure Authorization PIN</label>
                                <input id="authorization_pin" name="authorization_pin" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" value="{{ old('authorization_pin') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="123456" required>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-slate-900">Section 3: Platform Presets</h2>
                            <p class="text-sm text-slate-500">Configure the SaaS brand while the platform remains centrally controlled from Pakistan.</p>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="saas_platform_name" class="block text-sm font-medium text-slate-700 mb-1">SaaS Platform Name</label>
                                <input id="saas_platform_name" name="saas_platform_name" type="text" value="{{ old('saas_platform_name') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 focus:border-blue-500 focus:outline-none" placeholder="Shifa PMS" required>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                                <div class="font-semibold">Pakistan-controlled operation</div>
                                <div class="mt-1 text-emerald-700">All master platform settings are driven from Pakistan.</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Back to login</a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-slate-800 transition">Create Super Admin Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordLabel = document.getElementById('passwordStrengthLabel');
        const passwordBar = document.getElementById('passwordStrengthBar');

        function updatePasswordStrength(value) {
            let score = 0;
            if (value.length >= 8) score += 1;
            if (/[A-Z]/.test(value)) score += 1;
            if (/[0-9]/.test(value)) score += 1;
            if (/[^A-Za-z0-9]/.test(value)) score += 1;

            if (score <= 1) {
                passwordLabel.textContent = 'Weak';
                passwordBar.className = 'h-full w-1/4 rounded-full bg-red-500 transition-all duration-300';
            } else if (score === 2) {
                passwordLabel.textContent = 'Fair';
                passwordBar.className = 'h-full w-2/4 rounded-full bg-amber-500 transition-all duration-300';
            } else if (score === 3) {
                passwordLabel.textContent = 'Good';
                passwordBar.className = 'h-full w-3/4 rounded-full bg-blue-500 transition-all duration-300';
            } else {
                passwordLabel.textContent = 'Strong';
                passwordBar.className = 'h-full w-full rounded-full bg-emerald-500 transition-all duration-300';
            }
        }

        passwordInput.addEventListener('input', function () {
            updatePasswordStrength(this.value);
        });

        document.querySelectorAll('[data-toggle-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-toggle-target');
                const input = document.getElementById(targetId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.textContent = isPassword ? 'Hide' : 'Show';
            });
        });
    </script>
</body>
</html>
