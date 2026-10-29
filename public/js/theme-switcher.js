(function () {
    // Load once per page: a second copy would add a second click handler and undo every toggle.
    if (window.__artflowThemeLoaded) return;
    window.__artflowThemeLoaded = true;
    // One saved choice per signed-in user (or per guest), so each role can pick its own mode.
    function storageKey() {
        var scope = document.querySelector('meta[name="theme-scope"]');
        return 'artflow-theme' + (scope && scope.content ? ':' + scope.content : '');
    }
    var root = document.documentElement;

    function savedTheme() {
        try {
            var saved = window.localStorage.getItem(storageKey());
            return saved === 'dark' || saved === 'light'
                ? saved
                : (root.getAttribute('data-theme-default') === 'dark' ? 'dark' : 'light');
        } catch (error) {
            return root.getAttribute('data-theme-default') === 'dark' ? 'dark' : 'light';
        }
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        var button = document.querySelector('[data-theme-switcher]');

        if (!button) {
            return;
        }

        var isDark = theme === 'dark';
        button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        button.setAttribute('aria-label', 'Switch to ' + (isDark ? 'light' : 'dark') + ' theme');
        button.querySelector('.theme-switcher__label').textContent = isDark ? 'Light mode' : 'Dark mode';
        button.querySelector('.theme-switcher__icon').innerHTML = isDark
            ? '<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path></svg>'
            : '<svg viewBox="0 0 24 24" focusable="false"><path d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8 8.5 8.5 0 1 0 20.2 15.3Z"></path><path d="M16 3v4m-2-2h4"></path></svg>';
    }

    applyTheme(savedTheme());

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.querySelector('[data-theme-switcher]');

        if (!button) {
            return;
        }

        var dashboardLayout = document.querySelector('.dashboard-layout');
        var placeDashboardSwitcher = function () {
            if (!dashboardLayout) {
                return;
            }

            var mode = dashboardLayout.getAttribute('data-layout-mode') === 'sidebar' ? 'sidebar' : 'top';
            var slot = dashboardLayout.querySelector('[data-theme-switcher-slot="' + mode + '"]');

            if (slot && button.parentElement !== slot) {
                slot.appendChild(button);
            }
        };

        applyTheme(savedTheme());
        placeDashboardSwitcher();
        document.addEventListener('frontdesk-layout-change', placeDashboardSwitcher);
        window.addEventListener('resize', placeDashboardSwitcher);
        button.addEventListener('click', function () {
            var nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

            try {
                window.localStorage.setItem(storageKey(), nextTheme);
            } catch (error) {
                button.setAttribute('data-storage-unavailable', 'true');
            }

            // Also save it to the account, so the choice follows the user to any device.
            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) {
                fetch(window.location.origin + '/theme-preference', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content },
                    body: JSON.stringify({ theme: nextTheme }),
                    credentials: 'same-origin',
                }).catch(function () { /* offline: the local choice still applies */ });
            }

            applyTheme(nextTheme);
        });
    });
}());
