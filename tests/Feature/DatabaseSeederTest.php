<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_active_admin_from_configured_credentials(): void
    {
        config([
            'services.admin_user.name' => 'Bag Collection Admin',
            'services.admin_user.email' => 'admin@gmail.com',
            'services.admin_user.password' => 'secure-test-password',
        ]);

        $this->seed();

        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertSame('Bag Collection Admin', $admin->name);
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('secure-test-password', $admin->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_database_seeder_requires_an_admin_password(): void
    {
        config([
            'services.admin_user.name' => 'Bag Collection Admin',
            'services.admin_user.email' => 'admin@gmail.com',
            'services.admin_user.password' => '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Set ADMIN_USER_NAME, ADMIN_USER_EMAIL, and ADMIN_USER_PASSWORD');

        $this->seed();
    }
}
