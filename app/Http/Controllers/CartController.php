<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\ShoppingCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(ShoppingCartService $shoppingCartService): View
    {
        return view('cart.index', [
            'cart' => $shoppingCartService->contents(request()->user()),
        ]);
    }

    public function store(Request $request, ShoppingCartService $shoppingCartService): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($validated['product_variant_id']);
        $shoppingCartService->add($request->user(), $variant, $validated['quantity']);

        return redirect()->route('cart.index')->with('success', 'Item added to your cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 404);
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $variant = $cartItem->variant;

        if ($validated['quantity'] > $variant->stock) {
            throw ValidationException::withMessages(['quantity' => 'The requested quantity exceeds available stock.']);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 404);
        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Item removed from your cart.');
    }
}
