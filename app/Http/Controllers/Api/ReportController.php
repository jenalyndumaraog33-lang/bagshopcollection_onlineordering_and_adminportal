<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'sales' => [
                'all_time' => (float) Order::query()->sales()->sum('total'),
                'today' => (float) Order::query()->sales()->whereDate('created_at', today())->sum('total'),
                'last_7_days' => Order::query()
                    ->sales()
                    ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                    ->selectRaw('DATE(created_at) as date, SUM(total) as amount')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
            ],
            'orders' => [
                'pending' => Order::query()->where('status', Order::STATUS_PENDING)->count(),
                'processing' => Order::query()->where('status', Order::STATUS_PROCESSING)->count(),
                'ready' => Order::query()->where('status', Order::STATUS_READY)->count(),
            ],
            'inventory' => [
                'active_products' => Product::query()->where('is_active', true)->whereNull('archived_at')->count(),
                'low_stock_variants' => ProductVariant::query()
                    ->with('product:id,name')
                    ->where('is_active', true)
                    ->where('stock', '<=', 5)
                    ->orderBy('stock')
                    ->limit(25)
                    ->get(['id', 'product_id', 'name', 'stock']),
            ],
        ]);
    }
}
