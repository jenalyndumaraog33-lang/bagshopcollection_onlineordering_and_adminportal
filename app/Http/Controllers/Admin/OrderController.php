<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => Order::statuses(),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', ['order' => $order->load(['user', 'items'])]);
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderService $orderService,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Order::statuses())],
            'payment_status' => ['nullable', Rule::in(['pending', 'paid'])],
        ]);

        if (! empty($validated['status'])) {
            $orderService->changeStatus($order, $validated['status'], $request->user());
        }

        if (! empty($validated['payment_status']) && $validated['payment_status'] !== $order->payment_status) {
            $order->update(['payment_status' => $validated['payment_status']]);
            $activityLogger->record(
                $request->user(),
                'order.payment_updated',
                $order,
                "Payment status changed to {$validated['payment_status']}."
            );
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated.');
    }
}
