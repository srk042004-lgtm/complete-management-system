@php
    $iconPaths = [
        'connected' => 'M8 7h8M8 12h8M8 17h8M5 7h.01M5 12h.01M5 17h.01',
        'analytics' => 'M4 19V5m0 14h16M8 16v-5m4 5V7m4 9V9',
        'workflow' => 'M5 12h14m-6-6 6 6-6 6',
        'records' => 'M7 3.75h7l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Zm7 0v4h4m-8 5 2 2 4-4',
        'control' => 'M12 3.5 19 6v5.5c0 4.3-2.9 7.4-7 9-4.1-1.6-7-4.7-7-9V6l7-2.5Zm-3 8.7 2 2 4-4',
        'experience' => 'M20 8.5c0 4.2-8 9-8 9s-8-4.8-8-9a4 4 0 0 1 8-1.2 4 4 0 0 1 8 1.2Z',
        'finance' => 'M4 7.5A2.5 2.5 0 0 1 6.5 5H19v14H6.5A2.5 2.5 0 0 1 4 16.5v-9Zm0 0h15m-4 4.5h4m-4 0a1.5 1.5 0 1 0 0 3h4v-3h-4Z',
        'growth' => 'M4 17 10 11l4 4 6-8m-6 0h6v6',
    ];
@endphp
<svg class="reason-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <path d="{{ $iconPaths[$icon] ?? $iconPaths['connected'] }}"/>
</svg>
