<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createAdminUser(): \App\Models\User
    {
        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['is_super_admin' => true]
        );
        return \App\Models\User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
