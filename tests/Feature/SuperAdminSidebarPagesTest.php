<?php

namespace Tests\Feature;

use Tests\TestCase;

class SuperAdminSidebarPagesTest extends TestCase
{
    public function test_super_admin_sidebar_pages_require_authentication(): void
    {
        $this->get('/super-admin/hotels')->assertRedirect('/super-admin/login');
        $this->get('/super-admin/subscriptions')->assertRedirect('/super-admin/login');
        $this->get('/super-admin/billing')->assertRedirect('/super-admin/login');
        $this->get('/super-admin/team-access')->assertRedirect('/super-admin/login');
        $this->get('/super-admin/settings')->assertRedirect('/super-admin/login');
        $this->get('/super-admin/profile')->assertRedirect('/super-admin/login');
    }

    public function test_logout_redirects_to_super_admin_login_page(): void
    {
        $this->post('/logout')->assertRedirect('/super-admin/login');
    }
}
