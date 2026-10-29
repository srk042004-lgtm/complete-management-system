<?php

namespace Tests\Feature;

use Tests\TestCase;

class SuperAdminHotelRegistryCreateTest extends TestCase
{
    public function test_super_admin_hotel_registry_create_page_requires_authentication(): void
    {
        $this->get('/super-admin/hotels/create')->assertRedirect('/super-admin/login');
    }
}
