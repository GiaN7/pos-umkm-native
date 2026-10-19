<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_owner_with_an_interactive_password(): void
    {
        $this->artisan('pos:create-owner owner@pos.com --name="Owner Mitra Tech"')
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'a-secure-password')
            ->expectsQuestion('Ulangi password baru', 'a-secure-password')
            ->assertSuccessful();

        $owner = User::where('email', 'owner@pos.com')->firstOrFail();

        $this->assertSame('Owner Mitra Tech', $owner->name);
        $this->assertSame('owner', $owner->role);
        $this->assertTrue((bool) $owner->status);
        $this->assertTrue(Hash::check('a-secure-password', $owner->password));
    }

    public function test_it_creates_a_cashier_with_an_interactive_password(): void
    {
        $this->artisan('pos:create-kasir kasir@pos.com --name="Kasir Mitra Tech"')
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'a-secure-password')
            ->expectsQuestion('Ulangi password baru', 'a-secure-password')
            ->assertSuccessful();

        $cashier = User::where('email', 'kasir@pos.com')->firstOrFail();

        $this->assertSame('Kasir Mitra Tech', $cashier->name);
        $this->assertSame('kasir', $cashier->role);
        $this->assertTrue((bool) $cashier->status);
        $this->assertTrue(Hash::check('a-secure-password', $cashier->password));
    }

    public function test_it_requires_confirmation_before_converting_an_owner_to_cashier(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@pos.com',
            'password' => 'old-password',
            'role' => 'owner',
            'status' => true,
        ]);

        $this->artisan('pos:create-kasir owner@pos.com --name="Kasir Mitra Tech"')
            ->expectsQuestion('Akun owner@pos.com sudah ada dengan role owner. Jadikan kasir dan ganti password?', false)
            ->assertFailed();

        $this->assertSame('owner', $owner->fresh()->role);
    }

    public function test_it_requires_confirmation_before_promoting_an_existing_user(): void
    {
        $cashier = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@pos.com',
            'password' => 'old-password',
            'role' => 'kasir',
            'status' => true,
        ]);

        $this->artisan('pos:create-owner kasir@pos.com --name="Owner Mitra Tech"')
            ->expectsQuestion('Akun kasir@pos.com sudah ada dengan role kasir. Jadikan owner dan ganti password?', false)
            ->assertFailed();

        $this->assertSame('kasir', $cashier->fresh()->role);
    }

    public function test_it_can_reset_an_existing_owner_password_and_revoke_tokens(): void
    {
        $owner = User::create([
            'name' => 'Owner lama',
            'email' => 'owner@pos.com',
            'password' => 'old-password',
            'role' => 'owner',
            'status' => false,
        ]);
        $owner->createToken('old-session');

        $this->artisan('pos:create-owner owner@pos.com --name="Owner Mitra Tech"')
            ->expectsConfirmation('Akun owner@pos.com sudah ada dengan role owner. Jadikan owner dan ganti password?', 'yes')
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'a-new-secure-password')
            ->expectsQuestion('Ulangi password baru', 'a-new-secure-password')
            ->assertSuccessful();

        $owner->refresh();
        $this->assertSame('Owner Mitra Tech', $owner->name);
        $this->assertTrue((bool) $owner->status);
        $this->assertTrue(Hash::check('a-new-secure-password', $owner->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
