<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockCorrection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockCorrectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = StockCorrection::with(['product', 'user'])->orderBy('created_at', 'desc');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        $corrections = $query->paginate(10);

        return response()->json($corrections);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stok_sesudah' => 'required|integer|min:0',
            'tipe' => 'required|in:masuk,keluar,penyesuaian',
            'keterangan' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $stokSebelum = $product->stok;
        $stokSesudah = $request->stok_sesudah;
        $selisih = $stokSesudah - $stokSebelum;

        $correction = DB::transaction(function () use ($request, $product, $stokSebelum, $stokSesudah, $selisih) {
            // Create stock correction record
            $correction = StockCorrection::create([
                'product_id' => $request->product_id,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'selisih' => $selisih,
                'tipe' => $request->tipe,
                'keterangan' => $request->keterangan,
                'user_id' => auth()->id(),
            ]);

            // Update product stock
            $product->update(['stok' => $stokSesudah]);

            return $correction;
        });

        return response()->json([
            'message' => 'Koreksi stok berhasil',
            'correction' => $correction->load(['product', 'user']),
        ], 201);
    }
}
