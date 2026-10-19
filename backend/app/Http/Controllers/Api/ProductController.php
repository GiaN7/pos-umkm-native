<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query()->with('category');

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%')
                  ->orWhere('barcode', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->paginate(10);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $image = $request->file('image');
        unset($data['image'], $data['remove_image']);

        $imagePath = $image ? $this->storeCompressedImage($image) : null;

        try {
            $product = Product::create([
                ...$data,
                'image' => $imagePath,
            ]);
        } catch (Throwable $exception) {
            $this->deleteStoredImage($imagePath);
            throw $exception;
        }

        return new ProductResource($product->load('category'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return new ProductResource($product->load('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $image = $request->file('image');
        $removeImage = $request->boolean('remove_image');
        unset($data['image'], $data['remove_image']);

        $oldImagePath = $product->image;
        $newImagePath = $image ? $this->storeCompressedImage($image) : null;

        if ($newImagePath || $removeImage) {
            $data['image'] = $newImagePath;
        }

        try {
            $product->update($data);
        } catch (Throwable $exception) {
            $this->deleteStoredImage($newImagePath);
            throw $exception;
        }

        if ($newImagePath || $removeImage) {
            $this->deleteStoredImage($oldImagePath);
        }

        return new ProductResource($product->load('category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $imagePath = $product->image;
        $product->delete();
        $this->deleteStoredImage($imagePath);

        return response()->json(['message' => 'Product deleted successfully']);
    }

    private function storeCompressedImage(UploadedFile $image): string
    {
        $contents = app(\App\Support\ProductImageCompressor::class)->compress($image);
        $path = 'products/' . Str::uuid() . '.webp';

        if (!Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Unable to store the compressed product image.');
        }

        return $path;
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'products/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
