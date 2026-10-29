(function () {
    var sidebar = document.querySelector('[data-hotel-sidebar]');

    if (!sidebar) {
        return;
    }

    var storageKey = 'frontdesk_sidebar_collapsed';
    var layoutStorageKey = 'frontdesk_layout_mode';
    var body = document.body;
    var toggle = sidebar.querySelector('[data-hotel-sidebar-toggle]');
    var layoutToggles = document.querySelectorAll('[data-hotel-layout-toggle]');
    var themeButton = document.querySelector('[data-theme-switcher]');
    var themeSlot = sidebar.querySelector('[data-hotel-sidebar-theme]');
    var topbarThemeSlot = document.querySelector('[data-hotel-topbar-theme]');
    var topbarNav = document.querySelector('[data-hotel-topbar]');
    var scrim = document.querySelector('[data-hotel-sidebar-scrim]');
    var mobileOpenButton = document.querySelector('[data-hotel-sidebar-open]');

    function readCollapsed() {
        try {
            return window.localStorage.getItem(storageKey) === 'true';
        } catch (error) {
            return false;
        }
    }

    function saveCollapsed(collapsed) {
        try {
            window.localStorage.setItem(storageKey, String(collapsed));
        } catch (error) {
            toggle.setAttribute('data-storage-unavailable', 'true');
        }
    }

    function setCollapsed(collapsed, persist) {
        body.setAttribute('data-hotel-sidebar-collapsed', String(collapsed));
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expand navigation sidebar' : 'Collapse navigation sidebar');
        var controlLabel = toggle.querySelector('.hotel-sidebar__control-label');
        if (controlLabel) {
            controlLabel.textContent = collapsed ? 'Expand menu' : 'Collapse menu';
        }
        if (persist) {
            saveCollapsed(collapsed);
        }
    }

    function setLayoutMode(mode, persist) {
        var isTopbar = mode === 'topbar';
        var isMobile = window.matchMedia('(max-width: 767.98px)').matches;
        body.setAttribute('data-hotel-layout-mode', isTopbar ? 'topbar' : 'sidebar');
        sidebar.hidden = isTopbar;
        topbarNav.hidden = !isTopbar;
        if (isTopbar) {
            body.setAttribute('data-hotel-sidebar-mobile-open', 'false');
        }
        sidebar.inert = isTopbar || (isMobile && body.getAttribute('data-hotel-sidebar-mobile-open') !== 'true');
        var mobileOpen = body.getAttribute('data-hotel-sidebar-mobile-open') === 'true';
        scrim.hidden = isTopbar || !isMobile || !mobileOpen;
        mobileOpenButton.hidden = isTopbar || !isMobile || mobileOpen;
        layoutToggles.forEach(function (button) {
            button.textContent = isTopbar ? 'Switch to sidebar' : 'Switch to menu view';
            button.setAttribute('aria-label', isTopbar ? 'Switch to sidebar navigation' : 'Switch to menu navigation');
        });
        if (themeButton) {
            var activeThemeSlot = isTopbar ? topbarThemeSlot : themeSlot;
            if (activeThemeSlot) {
                activeThemeSlot.appendChild(themeButton);
            }
        }
        if (persist) {
            try {
                window.localStorage.setItem(layoutStorageKey, isTopbar ? 'topbar' : 'sidebar');
            } catch (error) {
                layoutToggles.forEach(function (button) {
                    button.setAttribute('data-storage-unavailable', 'true');
                });
            }
        }
    }

    function setMobileOpen(open) {
        var isTopbar = body.getAttribute('data-hotel-layout-mode') === 'topbar';
        body.setAttribute('data-hotel-sidebar-mobile-open', String(open));
        scrim.hidden = isTopbar || !open;
        sidebar.inert = isTopbar || !open;
        mobileOpenButton.hidden = isTopbar || open;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        toggle.querySelector('.hotel-sidebar__control-label').textContent = open ? 'Close menu' : 'Open menu';
        mobileOpenButton.setAttribute('aria-expanded', String(open));
    }

    setCollapsed(readCollapsed(), false);
    var savedLayoutMode = 'sidebar';
    try {
        savedLayoutMode = window.localStorage.getItem(layoutStorageKey) === 'topbar' ? 'topbar' : 'sidebar';
    } catch (error) {
        savedLayoutMode = 'sidebar';
    }
    setLayoutMode(savedLayoutMode, false);
    toggle.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 767.98px)').matches) {
            setMobileOpen(body.getAttribute('data-hotel-sidebar-mobile-open') !== 'true');
            return;
        }

        setCollapsed(body.getAttribute('data-hotel-sidebar-collapsed') !== 'true', true);
    });

    layoutToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            setLayoutMode(body.getAttribute('data-hotel-layout-mode') === 'topbar' ? 'sidebar' : 'topbar', true);
        });
    });

    mobileOpenButton.addEventListener('click', function () {
        setMobileOpen(true);
    });

    scrim.addEventListener('click', function () {
        setMobileOpen(false);
    });

    window.addEventListener('resize', function () {
        if (window.matchMedia('(max-width: 767.98px)').matches) {
            var isOpen = body.getAttribute('data-hotel-sidebar-mobile-open') === 'true';
            var isTopbar = body.getAttribute('data-hotel-layout-mode') === 'topbar';
            mobileOpenButton.hidden = isTopbar || isOpen;
            sidebar.inert = isTopbar || !isOpen;
            scrim.hidden = isTopbar || !isOpen;
        } else {
            body.setAttribute('data-hotel-sidebar-mobile-open', 'false');
            scrim.hidden = true;
            sidebar.inert = false;
            sidebar.hidden = body.getAttribute('data-hotel-layout-mode') === 'topbar';
            topbarNav.hidden = body.getAttribute('data-hotel-layout-mode') !== 'topbar';
            mobileOpenButton.hidden = true;
            setCollapsed(body.getAttribute('data-hotel-sidebar-collapsed') === 'true', false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && body.getAttribute('data-hotel-sidebar-mobile-open') === 'true') {
            setMobileOpen(false);
            toggle.focus();
        }
    });

    sidebar.addEventListener('click', function (event) {
        if (event.target.closest('a') && window.matchMedia('(max-width: 767.98px)').matches) {
            setMobileOpen(false);
        }
    });
}());
