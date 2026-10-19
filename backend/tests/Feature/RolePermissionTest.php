<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => true,
        ]);
    }

    private function kasir(): User
    {
        return User::create([
            'name' => 'Kasir',
            'email' => 'kasir@test.com',
            'password' => bcrypt('password'),
            'role' => 'kasir',
            'status' => true,
        ]);
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
        $this->getJson('/api/reports/sales?start_date=2026-10-01&end_date=2026-10-03')->assertStatus(401);
        $this->getJson('/api/stock-corrections')->assertStatus(401);
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_owner_can_access_dashboard_and_reports(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->getJson('/api/dashboard')->assertStatus(200);
        $this->actingAs($owner)->getJson('/api/reports/sales?start_date=2026-10-01&end_date=2026-10-03')
            ->assertStatus(200);
        $this->actingAs($owner)->getJson('/api/reports/profit-loss?start_date=2026-10-01&end_date=2026-10-03')
            ->assertStatus(200);
        $this->actingAs($owner)->getJson('/api/stock-corrections')->assertStatus(200);
    }

    public function test_kasir_gets_403_on_owner_only_endpoints(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->getJson('/api/dashboard')->assertStatus(403);
        $this->actingAs($kasir)->getJson('/api/reports/sales?start_date=2026-10-01&end_date=2026-10-03')
            ->assertStatus(403);
        $this->actingAs($kasir)->getJson('/api/reports/profit-loss?start_date=2026-10-01&end_date=2026-10-03')
            ->assertStatus(403);
        $this->actingAs($kasir)->getJson('/api/stock-corrections')->assertStatus(403);
        $this->actingAs($kasir)->getJson('/api/reports/sales/export?start_date=2026-10-01&end_date=2026-10-03')
            ->assertStatus(403);
    }

    public function test_kasir_can_read_master_data(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->getJson('/api/products')->assertStatus(200);
        $this->actingAs($kasir)->getJson('/api/categories')->assertStatus(200);
        $this->actingAs($kasir)->getJson('/api/customers')->assertStatus(200);
    }

    public function test_kasir_cannot_write_master_data(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->postJson('/api/products', [
            'name' => 'Produk Tes', 'category_id' => 1,
            'harga_jual' => 1000, 'harga_beli' => 500,
            'stok' => 10, 'stok_minimal' => 2,
        ])->assertStatus(403);

        $this->actingAs($kasir)->postJson('/api/categories', ['name' => 'Kategori Tes'])
            ->assertStatus(403);

        $this->actingAs($kasir)->postJson('/api/customers', ['name' => 'Customer Tes'])
            ->assertStatus(403);
    }

    public function test_owner_can_write_master_data(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->postJson('/api/categories', ['name' => 'Kategori Tes'])
            ->assertStatus(201);

        $this->assertDatabaseHas('categories', ['name' => 'Kategori Tes']);
    }
}
