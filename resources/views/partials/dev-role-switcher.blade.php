@if (app()->environment(['local', 'testing']) && auth('super_admin')->check())
    @php
        $devRoleOptions = [
            'owner' => 'Owner',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'receptionist' => 'Receptionist',
            'housekeeping' => 'Housekeeping',
        ];
    @endphp
    <aside class="dev-role-switcher" aria-label="Local role switcher">
        <button class="dev-role-switcher__toggle" type="button" aria-expanded="true" aria-controls="dev-role-switcher-panel">
            <span class="dev-role-switcher__label">Local role switcher</span>
            <span class="dev-role-switcher__chevron" aria-hidden="true">−</span>
        </button>
        <div class="dev-role-switcher__panel" id="dev-role-switcher-panel">
            <p>Switch to an isolated test account for this hotel.</p>
            <div class="dev-role-switcher__actions">
                @foreach ($devRoleOptions as $roleKey => $roleLabel)
                    <a href="{{ route('dev.switch-role', ['role' => $roleKey]) }}" target="_blank" rel="noopener" class="dev-role-switcher__role">{{ $roleLabel }}</a>
                @endforeach
            </div>
        </div>
    </aside>
    <style>
        .dev-role-switcher{position:fixed;right:0;bottom:24px;z-index:10000;display:flex;flex-direction:row-reverse;align-items:stretch;color:#0f172a;font:500 13px/1.4 ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        .dev-role-switcher__toggle{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;width:42px;min-height:200px;padding:16px 0;border:1px solid #0f766e;border-right:0;border-radius:12px 0 0 12px;background:#0f766e;color:#fff;box-shadow:0 12px 30px rgb(15 23 42 / 22%);cursor:pointer}
        .dev-role-switcher__toggle:hover{background:#115e59}
        .dev-role-switcher__toggle:focus-visible,.dev-role-switcher__role:focus-visible{outline:3px solid #2dd4bf;outline-offset:2px}
        .dev-role-switcher__label{writing-mode:vertical-rl;transform:rotate(180deg);font:700 12px/1 ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;letter-spacing:.02em;white-space:nowrap}
        .dev-role-switcher__chevron{font-size:20px;font-weight:700;line-height:1}
        .dev-role-switcher__panel{width:210px;padding:14px;border:1px solid #cbd5e1;border-right:0;border-radius:14px 0 0 14px;background:#fff;box-shadow:0 16px 42px rgb(15 23 42 / 22%)}
        .dev-role-switcher__panel[hidden]{display:none}
        .dev-role-switcher__panel p{margin:0 0 10px;color:#475569;font-size:11px}
        .dev-role-switcher__actions{display:flex;flex-direction:column;gap:7px}
        .dev-role-switcher__role{display:flex;align-items:center;justify-content:flex-start;min-height:36px;padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#1e293b;font:650 12px/1.2 ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;text-decoration:none;transition:background-color 150ms ease,border-color 150ms ease,color 150ms ease}
        .dev-role-switcher__role:hover{border-color:#5eead4;background:#f0fdfa;color:#115e59}
        html[data-theme="dark"] .dev-role-switcher__panel{border-color:#475569;background:#172331;color:#f1f5f9}
        html[data-theme="dark"] .dev-role-switcher__panel p{color:#cbd5e1}
        html[data-theme="dark"] .dev-role-switcher__role{border-color:#475569;background:#1e293b;color:#e2e8f0}
        html[data-theme="dark"] .dev-role-switcher__role:hover{border-color:#2dd4bf;background:#263746;color:#99f6e4}
        @media(prefers-reduced-motion:reduce){.dev-role-switcher__role{transition:none}}
    </style>
    <script>
        (() => {
            const toggle = document.querySelector('.dev-role-switcher__toggle');
            const panel = document.getElementById('dev-role-switcher-panel');
            const indicator = document.querySelector('.dev-role-switcher__chevron');
            const storageKey = 'devRoleSwitcherCollapsed';

            const apply = (collapsed) => {
                toggle.setAttribute('aria-expanded', String(!collapsed));
                panel.hidden = collapsed;
                indicator.textContent = collapsed ? '+' : '−';
            };

            // Remember the choice so the switcher stays minimised across pages until it is opened again.
            let collapsed = false;
            try { collapsed = window.localStorage.getItem(storageKey) === 'true'; } catch (error) { collapsed = false; }
            apply(collapsed);

            toggle?.addEventListener('click', () => {
                collapsed = toggle.getAttribute('aria-expanded') === 'true';
                apply(collapsed);
                try { window.localStorage.setItem(storageKey, String(collapsed)); } catch (error) { /* storage unavailable */ }
            });
        })();
    </script>
@endif
