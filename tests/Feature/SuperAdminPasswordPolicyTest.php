<?php

namespace Tests\Feature;

use Tests\TestCase;

class SuperAdminPasswordPolicyTest extends TestCase
{
    public function test_weak_passwords_are_rejected_for_super_admin_registration(): void
    {
        $response = $this->post(route('super-admin.register.store'), [
            'name' => 'Shahrukh Khan',
            'fname' => 'Wilayat Shah',
            'email' => 'superadmin@shifa.com',
            'number' => '03001234567',
            'password' => '1234',
            'address' => 'Nowshera, KPK, Pakistan',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
