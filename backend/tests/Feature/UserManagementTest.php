<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => 'password',
            'role' => 'owner',
            'status' => true,
        ]);
    }

    private function cashier(): User
    {
        return User::create([
            'name' => 'Kasir',
            'email' => 'kasir@test.com',
            'password' => 'password',
            'role' => 'kasir',
            'status' => true,
        ]);
    }

    public function test_owner_can_update_own_email_and_password_with_current_password(): void
    {
        $owner = $this->owner();
        $currentToken = $owner->createToken('current-session')->plainTextToken;
        $owner->createToken('other-session');

        $this->withToken($currentToken)->putJson('/api/account/credentials', [
            'current_password' => 'password',
            'name' => 'Owner Baru',
            'email' => 'owner-new@test.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Owner Baru')
            ->assertJsonPath('user.email', 'owner-new@test.com');

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'name' => 'Owner Baru',
            'email' => 'owner-new@test.com',
        ]);
        $this->assertTrue(Hash::check('new-password', $owner->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->withToken($currentToken)->getJson('/api/user')->assertOk();
    }

    public function test_owner_credential_update_rejects_wrong_current_password(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->putJson('/api/account/credentials', [
            'current_password' => 'wrong',
            'email' => 'owner-new@test.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'email' => 'owner@test.com']);
    }

    public function test_owner_can_update_cashier_credentials(): void
    {
        $owner = $this->owner();
        $cashier = $this->cashier();
        $cashier->createToken('cashier-session');

        $this->actingAs($owner)->putJson("/api/cashiers/{$cashier->id}", [
            'current_password' => 'password',
            'name' => 'Kasir Baru',
            'email' => 'cashier-new@test.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()
            ->assertJsonPath('cashier.name', 'Kasir Baru')
            ->assertJsonPath('cashier.email', 'cashier-new@test.com');

        $this->assertDatabaseHas('users', ['id' => $cashier->id, 'name' => 'Kasir Baru']);
        $this->assertTrue(Hash::check('new-password', $cashier->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_cashier_cannot_manage_account_credentials(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->getJson('/api/cashiers')->assertForbidden();
        $this->actingAs($cashier)->putJson('/api/account/credentials', [
            'current_password' => 'password',
            'email' => 'cashier-new@test.com',
        ])->assertForbidden();
    }

    public function test_cashier_account_endpoint_only_updates_cashier_accounts(): void
    {
        $owner = $this->owner();
        $otherOwner = User::create([
            'name' => 'Owner lain',
            'email' => 'other-owner@test.com',
            'password' => 'password',
            'role' => 'owner',
            'status' => true,
        ]);

        $this->actingAs($owner)->putJson("/api/cashiers/{$otherOwner->id}", [
            'current_password' => 'password',
            'email' => 'attacker@test.com',
        ])->assertNotFound();
    }
}
