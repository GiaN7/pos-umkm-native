<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display dashboard data.
     */
    public function index(Request $request)
    {
        $today = now()->toDateString();

        // Penjualan hari ini
        $penjualanHariIni = Sale::whereDate('created_at', $today)
            ->where('status', 'selesai')
            ->sum('total');

        $transaksiHariIni = Sale::whereDate('created_at', $today)
            ->where('status', 'selesai')
            ->count();

        // Stok menipis
        $stokMenipis = Product::where('status', true)
            ->whereColumn('stok', '<=', 'stok_minimal')
            ->orderBy('stok')
            ->get();

        // Top 5 produk terlaris
        $topProduk = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'selesai')
            ->select('products.id', 'products.name', DB::raw('SUM(sale_items.qty) as total_qty'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Penjualan 7 hari terakhir
        $penjualan7Hari = Sale::where('status', 'selesai')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as tanggal'), DB::raw('SUM(total) as total'))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return response()->json([
            'penjualan_hari_ini' => $penjualanHariIni,
            'transaksi_hari_ini' => $transaksiHariIni,
            'stok_menipis' => $stokMenipis,
            'top_produk' => $topProduk,
            'penjualan_7_hari' => $penjualan7Hari,
        ]);
    }
}
