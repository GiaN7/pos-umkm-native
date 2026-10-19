<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $this->route('product')?->id,
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $this->route('product')?->id,
            'harga_beli' => 'numeric|min:0',
            'harga_jual' => 'numeric|min:0',
            'stok' => 'integer|min:0',
            'stok_minimal' => 'integer|min:0',
            'status' => 'boolean',
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:10240',
                'dimensions:max_width=4000,max_height=4000',
            ],
            'remove_image' => 'sometimes|boolean',
        ];
    }
}
