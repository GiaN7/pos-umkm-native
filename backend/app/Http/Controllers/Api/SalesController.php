<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sale::with(['user', 'customer', 'items.product'])->orderBy('created_at', 'desc');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $sales = $query->paginate(10);

        return response()->json($sales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.diskon' => 'nullable|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'ppn' => 'nullable|numeric|min:0',
            'bayar' => 'required|numeric|min:0',
            'payment_method' => 'required|in:tunai,debit,qris,transfer',
        ]);

        $subtotal = 0;
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $itemSubtotal = $product->harga_jual * $item['qty'] - ($item['diskon'] ?? 0);
            $subtotal += $itemSubtotal;
        }

        $diskon = $request->diskon ?? 0;
        $ppn = $request->ppn ?? 0;
        $total = $subtotal - $diskon + $ppn;
        $bayar = $request->bayar;
        $kembali = $bayar - $total;

        if ($kembali < 0) {
            return response()->json(['message' => 'Pembayaran kurang'], 422);
        }

        $sale = DB::transaction(function () use ($request, $subtotal, $diskon, $ppn, $total, $bayar, $kembali) {
            // Generate nomor transaksi
            $noTransaksi = 'TRX-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'no_transaksi' => $noTransaksi,
                'user_id' => auth()->id(),
                'customer_id' => $request->customer_id,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'total' => $total,
                'bayar' => $bayar,
                'kembali' => $kembali,
                'payment_method' => $request->payment_method,
                'status' => 'selesai',
            ]);

            // Create sale items and update stock
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $itemSubtotal = $product->harga_jual * $item['qty'] - ($item['diskon'] ?? 0);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'harga' => $product->harga_jual,
                    'diskon' => $item['diskon'] ?? 0,
                    'subtotal' => $itemSubtotal,
                ]);

                // Update stock
                $product->decrement('stok', $item['qty']);
            }

            return $sale;
        });

        return response()->json([
            'message' => 'Transaksi berhasil',
            'sale' => $sale->load(['user', 'customer', 'items.product']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        return response()->json($sale->load(['user', 'customer', 'items.product']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'status' => 'required|in:selesai,retur,batal',
        ]);

        if ($request->status === 'retur' || $request->status === 'batal') {
            // Restore stock
            foreach ($sale->items as $item) {
                $item->product->increment('stok', $item->qty);
            }
        }

        $sale->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Transaksi diupdate',
            'sale' => $sale->load(['user', 'customer', 'items.product']),
        ]);
    }
}
