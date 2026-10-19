<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

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
    }

    private function validPayload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'name' => 'Kopi Susu',
            'sku' => 'SKU-001',
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'stok' => 20,
            'stok_minimal' => 5,
        ];
    }

    public function test_owner_can_create_product(): void
    {
        $category = Category::create(['name' => 'Minuman']);

        $this->actingAs($this->owner)
            ->postJson('/api/products', $this->validPayload($category))
            ->assertStatus(201);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-001', 'name' => 'Kopi Susu']);
    }

    public function test_product_requires_name_and_sku(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/products', ['name' => '', 'sku' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'sku']);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $category = Category::create(['name' => 'Minuman']);

        $this->actingAs($this->owner)
            ->postJson('/api/products', $this->validPayload($category))
            ->assertStatus(201);

        $this->actingAs($this->owner)
            ->postJson('/api/products', $this->validPayload($category))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_owner_can_update_product(): void
    {
        $category = Category::create(['name' => 'Minuman']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Lama',
            'sku' => 'SKU-LAMA',
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 10,
            'stok_minimal' => 2,
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/products/{$product->id}", [
                'category_id' => $category->id,
                'name' => 'Baru',
                'sku' => 'SKU-LAMA',
                'harga_jual' => 9999,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Baru', 'harga_jual' => 9999]);
    }

    public function test_owner_can_delete_product(): void
    {
        $category = Category::create(['name' => 'Minuman']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Hapus',
            'sku' => 'SKU-HAPUS',
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
            'stok_minimal' => 1,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_list_returns_data(): void
    {
        $category = Category::create(['name' => 'Minuman']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi',
            'sku' => 'SKU-KOPI',
            'harga_beli' => 5000,
            'harga_jual' => 10000,
            'stok' => 30,
            'stok_minimal' => 5,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/products');
        $response->assertStatus(200);

        $data = $response->json('data') ?? $response->json();
        $this->assertNotEmpty($data);
    }

    public function test_owner_can_upload_and_compress_a_product_photo(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Minuman']);

        $response = $this->actingAs($this->owner)
            ->post('/api/products', [
                ...$this->validPayload($category),
                'image' => UploadedFile::fake()->image('kopi.png', 800, 600),
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $imagePath = $response->json('data.image');
        $storedPath = str_replace('/storage/', '', $imagePath);
        $contents = Storage::disk('public')->get($storedPath);

        $this->assertStringEndsWith('.webp', $storedPath);
        $this->assertSame('RIFF', substr($contents, 0, 4));
        $this->assertSame('WEBP', substr($contents, 8, 4));
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_owner_can_replace_and_remove_a_product_photo(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Minuman']);
        $oldImagePath = 'products/old.webp';
        Storage::disk('public')->put($oldImagePath, 'old photo');
        $product = Product::create([
            ...$this->validPayload($category),
            'image' => $oldImagePath,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/api/products/{$product->id}", [
                ...$this->validPayload($category),
                '_method' => 'PUT',
                'image' => UploadedFile::fake()->image('kopi-baru.png', 400, 300),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $newImagePath = str_replace('/storage/', '', $response->json('data.image'));
        $this->assertNotSame($oldImagePath, $newImagePath);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($newImagePath);

        $this->actingAs($this->owner)
            ->post("/api/products/{$product->id}", [
                ...$this->validPayload($category),
                '_method' => 'PUT',
                'remove_image' => '1',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.image', null);

        Storage::disk('public')->assertMissing($newImagePath);
    }

    public function test_product_photo_rejects_unsupported_file_types(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Minuman']);

        $this->actingAs($this->owner)
            ->post('/api/products', [
                ...$this->validPayload($category),
                'image' => UploadedFile::fake()->create('produk.svg', 10, 'image/svg+xml'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }
}
