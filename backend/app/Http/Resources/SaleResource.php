<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_transaksi' => $this->no_transaksi,
            'user_id' => $this->user_id,
            'kasir' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone,
                ];
            }),
            'subtotal' => (float) $this->subtotal,
            'diskon' => (float) $this->diskon,
            'ppn' => (float) $this->ppn,
            'total' => (float) $this->total,
            'bayar' => (float) $this->bayar,
            'kembali' => (float) $this->kembali,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name ?? 'Produk Dihapus',
                        'product_sku' => $item->product?->sku ?? '-',
                        'qty' => (int) $item->qty,
                        'harga' => (float) $item->harga,
                        'diskon' => (float) $item->diskon,
                        'subtotal' => (float) $item->subtotal,
                    ];
                });
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
