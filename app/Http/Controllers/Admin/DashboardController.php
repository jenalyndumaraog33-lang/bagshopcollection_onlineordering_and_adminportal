<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $sales = Order::query()
            ->sales()
            ->selectRaw('DATE(created_at) as sales_date, SUM(total) as amount')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('sales_date')
            ->orderBy('sales_date')
            ->get();

        return view('admin.dashboard', [
            'totalSales' => Order::query()->sales()->sum('total'),
            'todaySales' => Order::query()->sales()->whereDate('created_at', today())->sum('total'),
            'pendingOrders' => Order::query()->where('status', Order::STATUS_PENDING)->count(),
            'lowStockVariants' => ProductVariant::query()
                ->with('product')
                ->where('is_active', true)
                ->where('stock', '<=', 5)
                ->orderBy('stock')
                ->limit(10)
                ->get(),
            'activeProducts' => Product::query()->where('is_active', true)->whereNull('archived_at')->count(),
            'recentOrders' => Order::query()->with('user')->latest()->limit(8)->get(),
            'sales' => $sales,
        ]);
    }
}
