<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use App\Services\ShoppingCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function show(Request $request, ShoppingCartService $shoppingCartService): CartResource
    {
        return CartResource::make($shoppingCartService->contents($request->user()));
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        return CartResource::collection(
            ShoppingCart::query()->with('items.variant.product')->latest()->paginate(20)
        );
    }

    public function adminShow(ShoppingCart $shoppingCart): CartResource
    {
        return CartResource::make($shoppingCart->load('items.variant.product'));
    }

    public function store(Request $request, ShoppingCartService $shoppingCartService): CartResource
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
        $variant = ProductVariant::with('product')->findOrFail($validated['product_variant_id']);
        $shoppingCartService->add($request->user(), $variant, $validated['quantity']);

        return CartResource::make($shoppingCartService->contents($request->user()));
    }

    public function update(Request $request, CartItem $cartItem): CartResource
    {
        abort_unless(
            $request->user()->isAdmin() || $cartItem->cart->user_id === $request->user()->id,
            404,
        );
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        if ($validated['quantity'] > $cartItem->variant->stock) {
            throw ValidationException::withMessages(['quantity' => 'The requested quantity exceeds available stock.']);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return CartResource::make($cartItem->cart()->with('items.variant.product')->firstOrFail());
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        abort_unless(
            $request->user()->isAdmin() || $cartItem->cart->user_id === $request->user()->id,
            404,
        );
        $cartItem->delete();

        return response()->json(['message' => 'Item removed from cart.']);
    }
}
