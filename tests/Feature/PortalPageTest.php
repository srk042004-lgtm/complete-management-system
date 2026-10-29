<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalPageTest extends TestCase
{
    public function test_public_portal_loads_with_the_existing_owner_and_designer_profiles(): void
    {
        $this->get(route('portal'))
            ->assertOk()
            ->assertSee('Raheel Ahmad')
            ->assertSee('Founder &amp; Product Visionary', false)
            ->assertSee('Shahrukh Khan')
            ->assertSee('System Designer &amp; Developer', false)
            ->assertSee(asset('images/Raheel ahmad.jpeg'), false)
            ->assertSee(asset('images/Shahrukh khan.jpeg'), false)
            ->assertSee('aria-label="Facebook"', false)
            ->assertSee('aria-label="Instagram"', false)
            ->assertSee('aria-label="LinkedIn"', false)
            ->assertSee('aria-label="WhatsApp"', false)
            ->assertSee('viewBox="0 0 24 24"', false)
            ->assertSee('data-theme-switcher', false)
            ->assertSee('<body class="portal-page">', false)
            ->assertSee(asset('css/theme-switcher.css'), false)
            ->assertSee(asset('js/theme-switcher.js'), false);
    }

    public function test_public_portal_is_also_available_at_the_explicit_portal_path(): void
    {
        $this->get(route('portal.page'))
            ->assertOk()
            ->assertSee('ArtFlow Management System')
            ->assertSee('Create a Demo Account');
    }

    public function test_public_login_links_direct_hotel_staff_to_the_hotel_login(): void
    {
        foreach ([route('portal'), route('pricing'), route('why-artflow'), route('features')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('href="'.route('hotel-admin.login').'"', false)
                ->assertSee('Hotel Staff Login');
        }

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Hotel staff?')
            ->assertSee(route('hotel-admin.login'), false);
    }

    public function test_demo_system_selector_disables_systems_that_are_not_live_yet(): void
    {
        $this->get(route('portal'))
            ->assertOk()
            ->assertSee('hidden ><div class="wrap demo-grid"', false)
            ->assertSee('Create a Demo Account')
            ->assertSee('value="hotel"', false)
            ->assertSee('value="clinic"', false)
            ->assertSee('value="school" disabled', false)
            ->assertSee('School Management (Coming Soon)')
            ->assertSee('value="restaurant" disabled', false)
            ->assertSee('Restaurant Management (Coming Soon)')
            ->assertSee('value="retail" disabled', false)
            ->assertSee('Retail &amp; POS Management (Coming Soon)', false);
    }

    public function test_pricing_page_keeps_demo_and_permanent_registration_separate(): void
    {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Register a permanent workspace or try a demo')
            ->assertSee(route('pricing.calculator', ['system' => 'hotel']), false)
            ->assertSee(route('portal').'#demo', false)
            ->assertDontSee('action="'.route('portal.demo-request').'"', false);
    }

    public function test_public_pages_linked_from_the_portal_load(): void
    {
        foreach ([
            '/why-artflow',
            '/pricing',
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/features')
            ->assertOk()
            ->assertSee('<body class="features-page">', false)
            ->assertSee('See These Features Live in Your Own Workflow');

        $this->get('/register/hotel')->assertRedirect(route('pricing.calculator', ['system' => 'hotel']));
        $this->get('/register/hotel?plan=small&cycle=monthly')->assertOk();

        $this->get('/pricing/calculator?system=hotel')
            ->assertOk()
            ->assertSee('data-theme-default="dark"', false)
            ->assertSee('data-theme-switcher', false);
    }

    public function test_reason_cards_use_consistent_inline_line_icons_on_both_public_pages(): void
    {
        foreach ([route('portal'), '/why-artflow'] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertSame(8, substr_count($response->getContent(), 'class="reason-icon-svg"'));
            $response->assertSee('stroke-linecap="round"', false);
        }
    }

    public function test_apos_menu_links_load_their_pages_with_the_shared_theme(): void
    {
        $pages = [
            ['apos.customers', 'Customers With Phone'],
            ['apos.products', 'APOS Products'],
            ['apos.invoices', 'Invoice Templates'],
            ['apos.softwares', 'Essential Software &amp; Drivers'],
            ['apos.requirements', 'Software Requirements'],
            ['apos.payments', 'Payments &amp; Return Policy'],
            ['apos.privacy', 'ArtFlow Privacy Policy'],
            ['apos.terms', 'Terms of Service'],
        ];

        foreach ($pages as [$routeName, $title]) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee($title, false)
                ->assertSee('data-theme-default="dark"', false)
                ->assertSee('data-theme-switcher', false)
                ->assertSee(asset('css/apos-pages.css'), false)
                ->assertSee('aria-label="APOS information pages"', false);
        }
    }

    public function test_unknown_apos_page_is_not_routed(): void
    {
        $this->get('/apos/not-a-page')->assertNotFound();
    }

    public function test_theme_switcher_is_injected_on_authenticated_area_login_pages(): void
    {
        $this->get(route('hotel-admin.login'))
            ->assertOk()
            ->assertSee('data-theme-switcher', false)
            ->assertSee(asset('css/theme-switcher.css'), false)
            ->assertSee(asset('js/theme-switcher.js'), false);
    }
}
