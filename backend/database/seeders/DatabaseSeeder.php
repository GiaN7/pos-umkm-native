<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Owner
        User::create([
            'name' => 'Owner',
            'email' => 'owner@pos.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => true,
        ]);

        // Create Kasir
        User::create([
            'name' => 'Kasir',
            'email' => 'kasir@pos.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'status' => true,
        ]);

        // Create Categories
        $minuman = Category::create([
            'name' => 'Minuman',
            'parent_id' => null,
            'status' => true,
        ]);

        $makanan = Category::create([
            'name' => 'Makanan',
            'parent_id' => null,
            'status' => true,
        ]);

        // Create Products
        Product::create([
            'category_id' => $minuman->id,
            'name' => 'Kopi Arabika 1kg',
            'sku' => 'KOPI-001',
            'barcode' => '8991000000011',
            'harga_beli' => 60000,
            'harga_jual' => 80000,
            'stok' => 50,
            'stok_minimal' => 10,
            'status' => true,
        ]);

        Product::create([
            'category_id' => $minuman->id,
            'name' => 'Gula Pasir 1kg',
            'sku' => 'GULA-001',
            'barcode' => '8991000000028',
            'harga_beli' => 12000,
            'harga_jual' => 15000,
            'stok' => 100,
            'stok_minimal' => 20,
            'status' => true,
        ]);

        Product::create([
            'category_id' => $makanan->id,
            'name' => 'Roti Tawar',
            'sku' => 'ROTI-001',
            'barcode' => '8991000000035',
            'harga_beli' => 8000,
            'harga_jual' => 12000,
            'stok' => 30,
            'stok_minimal' => 5,
            'status' => true,
        ]);
    }
}
