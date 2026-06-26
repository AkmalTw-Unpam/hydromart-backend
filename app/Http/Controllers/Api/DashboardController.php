<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Item, IncomingGood, OutgoingGood, StockMovement};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $today = Carbon::today();
            
            // Query basic dengan fallback nilai 0 jika kosong
            $totalItems      = Item::count() ?? 0;
            $inToday         = IncomingGood::whereDate('transaction_date', $today)->sum('quantity') ?? 0;
            $outToday        = OutgoingGood::whereDate('transaction_date', $today)->sum('quantity') ?? 0;
            $lowStockCount   = Item::whereRaw('stock <= min_stock')->count() ?? 0;
            $totalStockValue = Item::sum(DB::raw('stock * price')) ?? 0;

            // Buat data chart 30 hari tiruan super ringan
            $chartData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $chartData[] = [
                    'date'     => $date->format('d M'),
                    'incoming' => 0,
                    'outgoing' => 0,
                ];
            }

            // PERBAIKAN STRUKTUR DATA: Mengembalikan format flat & stats sekaligus demi keamanan React
            return response()->json([
                // Format Objek Stats Bawaan Anda
                'stats' => [
                    'total_items'       => (int) $totalItems,
                    'in_today'          => (float) $inToday,
                    'out_today'         => (float) $outToday,
                    'low_stock_count'   => (int) $lowStockCount,
                    'total_stock_value' => (float) $totalStockValue,
                ],
                
                // Format Tingkat Pertama (Flat) - Sering dicari oleh React Destructuring
                'totalItems'       => (int) $totalItems,
                'total_items'      => (int) $totalItems,
                'inToday'          => (float) $inToday,
                'in_today'         => (float) $inToday,
                'outToday'         => (float) $outToday,
                'out_today'        => (float) $outToday,
                'lowStockCount'    => (int) $lowStockCount,
                'low_stock_count'  => (int) $lowStockCount,
                'totalStockValue'  => (float) $totalStockValue,
                'total_stock_value'=> (float) $totalStockValue,
                
                // Array pendukung agar tidak undefined
                'chart_data'       => $chartData,
                'chartData'        => $chartData,
                'top_items'        => [],
                'topItems'         => [],
                'low_stock_items'  => [],
                'lowStockItems'    => [],
                'recent_movements' => [],
                'recentMovements'  => [],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}