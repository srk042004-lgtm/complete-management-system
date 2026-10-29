            @include('partials.designer-credit')
            </div>
    </div>
</div>
<script src="{{ asset('js/theme-switcher.js') }}"></script>
<script src="{{ asset('js/hotel-sidebar.js') }}"></script>
<script src="{{ asset('js/action-tooltips.js') }}" defer></script>
<script>
(function () {
    // Prefetch a page when the pointer or keyboard focus reaches its link, so the click feels instant.
    var seen = {};
    function prefetch(link) {
        var href = link.getAttribute('href');
        if (!href || seen[href]) return;
        seen[href] = true;
        var hint = document.createElement('link');
        hint.rel = 'prefetch';
        hint.href = href;
        document.head.appendChild(hint);
    }
    document.querySelectorAll('.hotel-sidebar__link, .hotel-topbar__link').forEach(function (link) {
        link.addEventListener('pointerenter', function () { prefetch(link); }, { passive: true });
        link.addEventListener('focus', function () { prefetch(link); });
    });
})();
</script>
