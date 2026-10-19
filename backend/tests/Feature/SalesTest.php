<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@test.com',
            'password' => bcrypt('password'),
            'role' => 'kasir',
            'status' => true,
        ]);
    }

    private function makeProduct(int $stok = 50, int $harga = 10000): Product
    {
        static $i = 0;
        $i++;

        $category = Category::create(['name' => 'Kategori ' . $i]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Produk ' . $i,
            'sku' => 'SKU-' . $i . '-' . uniqid(),
            'harga_beli' => 5000,
            'harga_jual' => $harga,
            'stok' => $stok,
            'stok_minimal' => 5,
        ]);
    }

    public function test_kasir_can_create_sale_and_stock_is_deducted(): void
    {
        $product = $this->makeProduct(stok: 50, harga: 10000);

        $response = $this->actingAs($this->kasir)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'qty' => 3]],
            'bayar' => 50000,
            'payment_method' => 'tunai',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('sale.status', 'selesai')
            ->assertJsonPath('sale.total', '30000.00')
            ->assertJsonPath('sale.kembali', '20000.00');

        $this->assertEquals(47, $product->fresh()->stok, 'Stok harus berkurang 3');
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
    }

    public function test_sale_with_insufficient_payment_is_rejected(): void
    {
        $product = $this->makeProduct(stok: 10, harga: 10000);

        $this->actingAs($this->kasir)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'qty' => 3]],
            'bayar' => 10000, // kurang dari 30000
            'payment_method' => 'tunai',
        ])->assertStatus(422);

        $this->assertEquals(10, $product->fresh()->stok, 'Stok tidak boleh berubah');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_sale_requires_valid_payment_method(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->kasir)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'bayar' => 20000,
            'payment_method' => 'bitcoin',
        ])->assertStatus(422);
    }

    public function test_sale_requires_at_least_one_item(): void
    {
        $this->actingAs($this->kasir)->postJson('/api/sales', [
            'items' => [],
            'bayar' => 20000,
            'payment_method' => 'tunai',
        ])->assertStatus(422);
    }

    public function test_owner_can_retur_sale_and_stock_restored(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => true,
        ]);

        $product = $this->makeProduct(stok: 20, harga: 10000);

        $this->actingAs($this->kasir)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'qty' => 5]],
            'bayar' => 100000,
            'payment_method' => 'tunai',
        ])->assertStatus(201);

        $this->assertEquals(15, $product->fresh()->stok);

        $sale = Sale::first();

        // Kasir tidak boleh retur
        $this->actingAs($this->kasir)
            ->putJson("/api/sales/{$sale->id}/status", ['status' => 'retur'])
            ->assertStatus(403);

        $this->assertEquals(15, $product->fresh()->stok, 'Stok tidak boleh berubah oleh kasir');

        // Owner boleh retur
        $this->actingAs($owner)
            ->putJson("/api/sales/{$sale->id}/status", ['status' => 'retur'])
            ->assertStatus(200);

        $this->assertEquals(20, $product->fresh()->stok, 'Stok harus kembali 20');
        $this->assertEquals('retur', $sale->fresh()->status);
    }

    public function test_sale_list_is_accessible_by_both_roles(): void
    {
        $this->actingAs($this->kasir)->getJson('/api/sales')->assertStatus(200);

        $owner = User::create([
            'name' => 'Owner', 'email' => 'owner@test.com',
            'password' => bcrypt('password'), 'role' => 'owner', 'status' => true,
        ]);
        $this->actingAs($owner)->getJson('/api/sales')->assertStatus(200);
    }
}
