<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => true,
        ]);

        $category = Category::create(['name' => 'Minuman']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Arabika',
            'sku' => 'SKU-KOPI',
            'harga_beli' => 60000,
            'harga_jual' => 80000,
            'stok' => 40,
            'stok_minimal' => 5,
        ]);

        $this->seedSale();
    }

    private function seedSale(): void
    {
        $sale = Sale::create([
            'no_transaksi' => 'TRX-TEST-0001',
            'user_id' => $this->owner->id,
            'customer_id' => null,
            'subtotal' => 80000,
            'diskon' => 0,
            'ppn' => 0,
            'total' => 80000,
            'bayar' => 100000,
            'kembali' => 20000,
            'payment_method' => 'tunai',
            'status' => 'selesai',
            'created_at' => now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'harga' => 80000,
            'diskon' => 0,
            'subtotal' => 80000,
        ]);
    }

    private function params(): array
    {
        return [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ];
    }

    public function test_dashboard_returns_expected_keys(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/dashboard');

        $response->assertStatus(200)->assertJsonStructure([
            'penjualan_hari_ini',
            'transaksi_hari_ini',
            'stok_menipis',
            'top_produk',
            'penjualan_7_hari',
        ]);

        $this->assertEquals(1, $response->json('transaksi_hari_ini'));
    }

    public function test_low_stock_products_are_reported(): void
    {
        // Buat produk dengan stok di bawah stok_minimal
        Product::create([
            'category_id' => null,
            'name' => 'Gula Menipis',
            'sku' => 'SKU-GULA-LOW',
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 2,
            'stok_minimal' => 10,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/dashboard');

        $lowStock = collect($response->json('stok_menipis'));
        $this->assertTrue(
            $lowStock->contains('sku', 'SKU-GULA-LOW'),
            'Produk dengan stok <= stok_minimal harus muncul di alert'
        );
    }

    public function test_sales_report_contains_summary(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/reports/sales?' . http_build_query($this->params()));

        $response->assertStatus(200)->assertJsonStructure([
            'summary' => [
                'total_transaksi', 'total_subtotal', 'total_diskon',
                'total_ppn', 'total_penjualan', 'total_bayar',
            ],
            'daily',
            'payment_methods',
            'top_products',
        ]);

        $this->assertEquals(1, $response->json('summary.total_transaksi'));
        $this->assertEquals('80000.00', $response->json('summary.total_penjualan'));
    }

    public function test_profit_loss_report_calculates_gross_profit(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/reports/profit-loss?' . http_build_query($this->params()));

        $response->assertStatus(200)->assertJsonStructure([
            'pendapatan', 'hpp', 'diskon', 'ppn', 'laba_kotor',
        ]);

        // Pendapatan 80000, HPP 60000 → laba 20000
        $this->assertEquals(20000, $response->json('laba_kotor'));
    }

    public function test_report_requires_date_range(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/reports/sales')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_sales_export_downloads_csv(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/api/reports/sales/export?' . http_build_query($this->params()));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'laporan-penjualan-',
            $response->headers->get('Content-Disposition')
        );

        $content = $response->streamedContent();
        $this->assertStringContainsString('No. Transaksi', $content);
        $this->assertStringContainsString('TRX-TEST-0001', $content);
        $this->assertStringContainsString('Kopi Arabika x1', $content);
    }

    public function test_profit_loss_export_downloads_csv(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/api/reports/profit-loss/export?' . http_build_query($this->params()));

        $response->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('Laporan Laba Rugi', $content);
        $this->assertStringContainsString('Laba Kotor', $content);
    }
}
