<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()->with('items');

        if (! $request->user()->isAdmin()) {
            $orders->whereBelongsTo($request->user());
        }

        return OrderResource::collection($orders->latest()->paginate(20));
    }

    public function store(Request $request, OrderService $orderService): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $order = $orderService->place($request->user(), $validated);

        return OrderResource::make($order)->response()->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        if (! $request->user()->isAdmin()) {
            $order = $request->user()->orders()->whereKey($order->id)->firstOrFail();
        }

        return OrderResource::make($order->load('items'));
    }

    public function updateStatus(Request $request, Order $order, OrderService $orderService): OrderResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::statuses())],
        ]);
        $order = $orderService->changeStatus($order, $validated['status'], $request->user());

        return OrderResource::make($order->load('items'));
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::query()->with('items')->latest()->paginate(20)
        );
    }
}
