<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\ShoppingCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(ShoppingCartService $shoppingCartService): View
    {
        $cart = $shoppingCartService->contents(request()->user());
        abort_if($cart->items->isEmpty(), 404, 'Your cart is empty.');

        return view('checkout.create', ['cart' => $cart]);
    }

    public function store(Request $request, OrderService $orderService): RedirectResponse
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

        return redirect()->route('orders.show', $order)->with('success', "Order {$order->order_number} placed.");
    }
}
