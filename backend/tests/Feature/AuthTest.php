<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function seedUser(): User
    {
        return User::create([
            'name' => 'Owner Testing',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => true,
        ]);
    }

    public function test_login_success_with_valid_credentials(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/login', [
            'email' => 'owner@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/login', [
            'email' => 'owner@test.com',
            'password' => 'salah',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'tidakada@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_endpoint_requires_token(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = $this->seedUser();

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertStatus(200)
            ->assertJson(['email' => 'owner@test.com', 'role' => 'owner']);
    }

    public function test_logout_revokes_token(): void
    {
        $this->seedUser();

        $login = $this->postJson('/api/login', [
            'email' => 'owner@test.com',
            'password' => 'password',
        ])->assertStatus(200);

        $token = $login->json('token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertStatus(200);

        // Flush cache guard Sanctum supaya token hasil revoke terbaca
        $this->app['auth']->forgetGuards();

        // Token sudah tidak berlaku
        $this->withToken($token)->getJson('/api/user')->assertStatus(401);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
