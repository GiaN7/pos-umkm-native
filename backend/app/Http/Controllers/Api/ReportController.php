<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Laporan penjualan harian/bulanan.
     */
    public function sales(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Summary
        $summary = Sale::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'selesai')
            ->selectRaw('COUNT(*) as total_transaksi')
            ->selectRaw('SUM(subtotal) as total_subtotal')
            ->selectRaw('SUM(diskon) as total_diskon')
            ->selectRaw('SUM(ppn) as total_ppn')
            ->selectRaw('SUM(total) as total_penjualan')
            ->selectRaw('SUM(bayar) as total_bayar')
            ->first();

        // Daily breakdown
        $daily = Sale::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'selesai')
            ->selectRaw('DATE(created_at) as tanggal')
            ->selectRaw('COUNT(*) as jumlah_transaksi')
            ->selectRaw('SUM(total) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // Payment method breakdown
        $paymentMethods = Sale::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'selesai')
            ->selectRaw('payment_method')
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw('SUM(total) as total')
            ->groupBy('payment_method')
            ->get();

        // Top products
        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('sales.status', 'selesai')
            ->select('products.id', 'products.name')
            ->selectRaw('SUM(sale_items.qty) as total_qty')
            ->selectRaw('SUM(sale_items.subtotal) as total_subtotal')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => $summary,
            'daily' => $daily,
            'payment_methods' => $paymentMethods,
            'top_products' => $topProducts,
        ]);
    }

    /**
     * Laporan laba rugi.
     */
    public function profitLoss(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json($this->profitLossData($request));
    }

    /**
     * Export laporan penjualan ke CSV (Excel).
     */
    public function exportSales(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $sales = Sale::with('items.product')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'selesai')
            ->orderBy('created_at')
            ->get();

        $filename = "laporan-penjualan-{$startDate}-{$endDate}.csv";

        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($out, [
                'No. Transaksi', 'Tanggal', 'Kasir', 'Customer',
                'Subtotal', 'Diskon', 'PPN', 'Total', 'Bayar', 'Kembalian',
                'Metode Bayar', 'Detail Item',
            ], ';');

            foreach ($sales as $sale) {
                $items = $sale->items->map(function ($item) {
                    return "{$item->product?->name} x{$item->qty} = Rp " . number_format($item->subtotal, 0, ',', '.');
                })->implode(' | ');

                fputcsv($out, [
                    $sale->no_transaksi,
                    $sale->created_at->format('d/m/Y H:i'),
                    $sale->user?->name,
                    $sale->customer?->name ?? '-',
                    $sale->subtotal,
                    $sale->diskon,
                    $sale->ppn,
                    $sale->total,
                    $sale->bayar,
                    $sale->kembali,
                    strtoupper($sale->payment_method),
                    $items,
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export laporan laba rugi ke CSV (Excel).
     */
    public function exportProfitLoss(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $this->profitLossData($request);

        $filename = "laba-rugi-{$request->start_date}-{$request->end_date}.csv";

        return response()->streamDownload(function () use ($data, $request) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['Laporan Laba Rugi'], ';');
            fputcsv($out, ['Periode', "{$request->start_date} s/d {$request->end_date}"], ';');
            fputcsv($out, [], ';');

            fputcsv($out, ['Keterangan', 'Nilai (Rp)'], ';');
            fputcsv($out, ['Pendapatan', $data['pendapatan']], ';');
            fputcsv($out, ['Harga Pokok Penjualan (HPP)', $data['hpp']], ';');
            fputcsv($out, ['Diskon', $data['diskon']], ';');
            fputcsv($out, ['PPN', $data['ppn']], ';');
            fputcsv($out, ['Laba Kotor', $data['laba_kotor']], ';');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Ambil data laba rugi (dipakai oleh profitLoss & exportProfitLoss).
     */
    private function profitLossData(Request $request): array
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $pendapatan = Sale::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'selesai')
            ->sum('total');

        $hpp = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('sales.status', 'selesai')
            ->sum(DB::raw('sale_items.qty * products.harga_beli'));

        $diskon = Sale::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'selesai')
            ->sum('diskon');

        $ppn = Sale::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'selesai')
            ->sum('ppn');

        return [
            'pendapatan' => $pendapatan,
            'hpp' => $hpp,
            'diskon' => $diskon,
            'ppn' => $ppn,
            'laba_kotor' => $pendapatan - $hpp,
        ];
    }
}
