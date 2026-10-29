<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\HotelRoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectThemeSwitcher
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $routeName = $request->route()?->getName();

        // The light/dark toggle is available on every page except the super-admin console and developer tools.
        if (! is_string($routeName) || str_starts_with($routeName, 'super-admin') || str_starts_with($routeName, 'dev.')) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        $content = $response->getContent();

        if (! is_string($content) || ! str_contains(strtolower($contentType), 'text/html')) {
            return $response;
        }

        $hasThemeSwitcher = preg_match(
            '/<button\b[^>]*\bdata-theme-switcher(?:\s|=|>)/i',
            $content
        ) === 1;
        if (! $hasThemeSwitcher) {
            $defaultTheme = $request->is('pricing/calculator') || $request->is('apos/*') ? 'dark' : 'light';
            $content = preg_replace(
                '/<html\b([^>]*)>/i',
                '<html$1 data-theme-default="'.$defaultTheme.'">',
                $content,
                1
            ) ?? $content;
            $themeCss = asset('css/theme-switcher.css').'?v='.filemtime(public_path('css/theme-switcher.css'));
            $assets = '<link rel="stylesheet" href="'.e($themeCss).'">'
                .'<script src="'.e(asset('js/theme-switcher.js')).'"></script>';
            $content = preg_replace('/(<head\b[^>]*>)/i', '$1'.$assets, $content, 1) ?? $content;
            $switcher = '<button class="theme-switcher" type="button" data-theme-switcher aria-label="Switch to dark theme" aria-pressed="false">'
                .'<span class="theme-switcher__icon" aria-hidden="true"></span>'
                .'<span class="theme-switcher__label">Dark mode</span>'
                .'</button>';
            $content = preg_replace('/<\/body\s*>/i', $switcher.'</body>', $content, 1) ?? $content;
        }

        // The sidebar belongs to hotel workspace pages only; public pages keep just the theme switcher.
        $sidebar = $response->getStatusCode() === Response::HTTP_OK && ! $this->isPublicRoute((string) $routeName)
            ? $this->renderSidebar($request)
            : null;
        if ($sidebar !== null && ! str_contains($content, 'data-hotel-sidebar')) {
            $sidebarCss = asset('css/hotel-sidebar.css').'?v='.filemtime(public_path('css/hotel-sidebar.css'));
            $sidebarAssets = '<link rel="stylesheet" href="'.e($sidebarCss).'">'
                .'<script defer src="'.e(asset('js/hotel-sidebar.js')).'"></script>';
            $content = preg_replace('/(<head\b[^>]*>)/i', '$1'.$sidebarAssets, $content, 1) ?? $content;
            $content = preg_replace_callback('/<body\b([^>]*)>/i', static function (array $matches): string {
                $attributes = $matches[1];
                if (preg_match('/\bclass=(["\'])(.*?)\1/i', $attributes, $classMatch) === 1) {
                    $attributes = str_replace($classMatch[0], 'class='.$classMatch[1].trim($classMatch[2].' hotel-sidebar-page').$classMatch[1], $attributes);
                } else {
                    $attributes .= ' class="hotel-sidebar-page"';
                }

                return '<body'.$attributes.'>';
            }, $content, 1) ?? $content;
            $content = preg_replace_callback('/<body\b[^>]*>/i', static fn (array $matches): string => $matches[0].$sidebar['topbar'], $content, 1) ?? $content;
            $content = preg_replace('/<\/body\s*>/i', $sidebar['sidebar'].'</body>', $content, 1) ?? $content;
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function renderSidebar(Request $request): ?array
    {
        $routeName = $request->route()?->getName();
        $user = $request->user();

        if (
            ! is_string($routeName)
            || ! $user instanceof User
            || $user->status !== 'active'
            || $user->tenant?->status !== 'active'
            || ! $this->isHotelWorkspaceRoute($routeName)
        ) {
            return null;
        }

        $role = HotelRoleCatalog::normalize((string) $user->role);
        $roleLabel = HotelRoleCatalog::headerLabel($role);
        $roleClass = in_array($role, ['admin', 'owner', 'manager', 'receptionist', 'housekeeping'], true) ? $role : 'default';
        $roleBadge = '<span class="hotel-role-badge hotel-role-badge--'.e($roleClass).'" aria-label="Role: '.e($roleLabel).'">'
            .'<span class="hotel-role-badge__dot" aria-hidden="true"></span>'.e($roleLabel).'</span>';
        $logoutForm = '<form class="hotel-logout-form" action="'.e(route('hotel-admin.logout')).'" method="POST">'
            .'<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
            .'<button class="hotel-logout-button" type="submit" aria-label="Log out of hotel account">Log out</button></form>';
        $capabilities = HotelRoleCatalog::allowedCapabilities($role);
        $tenant = $user->tenant;

        $dashboard = ['Dashboard', '⌂', 'hotel.role.dashboard', 'dashboard.read', null, ['role' => $role]];
        $sections = match ($role) {
            'owner' => [
                'Executive' => [
                    $dashboard,
                    ['Room Rate Structures', '▦', 'frontdesk.inventory', 'rooms.read', 'rooms', []],
                    ['Analytics & Ledgers', '▥', 'home.reports', 'reports.read', 'reports', []],
                    ['Audit Logs', '◷', 'hotel.audit-logs', 'audit.read', null, []],
                    ['Staff Management · Admins & Managers', '♙', 'hotel.role.dashboard', 'staff.manage', 'staff', ['role' => 'owner']],
                    ['SaaS Subscription', '$', 'account.subscription', 'subscription.manage', null, []],
                ],
            ],
            'admin' => [
                'Hotel operations' => [
                    $dashboard,
                    ['Live Room Grid', '▦', 'hotel.role.dashboard', 'frontdesk.dashboard', null, ['role' => 'admin']],
                    ['Reservations & Front Desk', '▤', 'reservations.index', 'reservations.read', 'reservations', []],
                    ['Housekeeping & Maintenance', '✧', 'housekeeping.index', 'housekeeping.access', 'housekeeping', []],
                    ['Staff Management · Front Desk & Housekeeping', '♙', 'hotel.role.dashboard', 'staff.manage', 'staff', ['role' => 'admin']],
                    ['Shift & Night Audit', '◷', 'audit.night', 'night-audit.run', null, []],
                ],
            ],
            default => [
                'Workspace' => [
                    $dashboard,
                    ['Front Desk', '▣', 'frontdesk.dashboard', 'frontdesk.dashboard', null, []],
                    ['Reservations', '▤', 'reservations.index', 'reservations.read', 'reservations', []],
                    ['Housekeeping', '✧', 'housekeeping.index', 'housekeeping.access', 'housekeeping', []],
                ],
            ],
        };

        $sidebarHtml = '<button class="hotel-sidebar__mobile-open" type="button" data-hotel-sidebar-open aria-expanded="false" aria-label="Open navigation menu"><span aria-hidden="true">☰</span></button>'
            .'<button class="hotel-sidebar__scrim" type="button" data-hotel-sidebar-scrim hidden aria-label="Close navigation menu"></button>'
            .'<aside class="hotel-sidebar" data-hotel-sidebar aria-label="Hotel navigation">'
            .'<div class="hotel-sidebar__brand"><strong>ARTFLOW HOTEL</strong><span>'.e($tenant->hotel_name ?? $tenant->name).'</span></div>';
        $topbarLinks = '';
        $topbarActions = match ($role) {
            'admin' => ['Reservations & Front Desk', 'Housekeeping & Maintenance', 'Shift & Night Audit'],
            'owner' => [],
            default => ['Front Desk', 'Reservations', 'Housekeeping'],
        };

        foreach ($sections as $title => $items) {
            $links = '';
            foreach ($items as [$label, $icon, $target, $capability, $feature, $parameters]) {
                if (
                    ! in_array($capability, $capabilities, true)
                    || ($feature !== null && ! $tenant->hasFeature($feature))
                ) {
                    continue;
                }

                $url = route($target, $parameters);
                if ($label === 'Live Room Grid') {
                    $url .= '#room-grid';
                } elseif (str_starts_with($label, 'Staff Management')) {
                    $url .= '#staff-management';
                }
                $active = $this->isActiveRoute($routeName, $target);
                $links .= '<li><a class="hotel-sidebar__link" href="'.e($url).'"'
                    .($active ? ' aria-current="page"' : '')
                    .' title="'.e($label).'"><span class="hotel-sidebar__icon" aria-hidden="true">'.e($icon).'</span>'
                    .'<span class="hotel-sidebar__label">'.e($label).'</span></a></li>';
                if (in_array($label, $topbarActions, true)) {
                    $topbarLinks .= '<a class="hotel-topbar__link" href="'.e($url).'"'
                        .($active ? ' aria-current="page"' : '')
                        .' title="'.e($label).'">'.e($label).'</a>';
                }
            }

            if ($links !== '') {
                $sidebarHtml .= '<div class="hotel-sidebar__group-title">'.e($title).'</div><ul class="hotel-sidebar__links">'.$links.'</ul>';
            }
        }

        $sidebarHtml .= '<div class="hotel-sidebar__footer">'
            .'<div class="hotel-sidebar__theme-slot" data-hotel-sidebar-theme></div>'
            .'<button type="button" class="hotel-sidebar__mode-toggle" data-hotel-layout-toggle aria-label="Switch to menu navigation">Switch to menu view</button>'
            .'<button type="button" class="hotel-sidebar__control" data-hotel-sidebar-toggle aria-expanded="true" aria-label="Collapse navigation sidebar"><span class="hotel-sidebar__control-label">Collapse menu</span></button>'
            .$logoutForm
            .'</div></aside>';

        $topbarHtml = '<nav class="hotel-topbar" data-hotel-topbar aria-label="Quick actions">'
            .'<div class="hotel-topbar__heading"><strong>QUICK ACTIONS</strong><div class="hotel-topbar__actions"><span class="hotel-role-identity">'.e($user->name).' '.$roleBadge.'</span><div class="hotel-topbar__theme-slot" data-hotel-topbar-theme></div><button type="button" class="hotel-topbar__mode-toggle" data-hotel-layout-toggle aria-label="Switch to sidebar navigation">Switch to sidebar</button>'.$logoutForm.'</div></div>'
            .'<div class="hotel-topbar__links">'.$topbarLinks.'</div>'
            .'</nav>';

        return ['topbar' => $topbarHtml, 'sidebar' => $sidebarHtml];
    }

    private function isHotelWorkspaceRoute(string $routeName): bool
    {
        if ($routeName === 'hotel.role.dashboard') {
            return true;
        }

        if (preg_match('/^(owner|admin|manager|receptionist|housekeeping|accountant|corporate|maintenance)\.dashboard$/', $routeName) === 1) {
            return true;
        }

        // Public marketing and registration pages share the same switcher.
        if (str_starts_with($routeName, 'apos.')) {
            return true;
        }

        foreach (['hotel-admin.login', 'login', 'hotel.audit-logs', 'home.reports', 'account.subscription', 'audit.night', 'portal', 'portal.page', 'pricing', 'pricing.calculator', 'features', 'why-artflow', 'industry.register', 'home.settings'] as $exactRoute) {
            if ($routeName === $exactRoute) {
                return true;
            }
        }

        return false;
    }

    private function isPublicRoute(string $routeName): bool
    {
        if (str_starts_with($routeName, 'apos.')) {
            return true;
        }

        return in_array($routeName, ['portal', 'portal.page', 'pricing', 'pricing.calculator', 'features', 'why-artflow', 'industry.register'], true);
    }

    private function isActiveRoute(string $currentRoute, string $targetRoute): bool
    {
        return $currentRoute === $targetRoute
            || str_starts_with($currentRoute, $targetRoute.'.');
    }
}
