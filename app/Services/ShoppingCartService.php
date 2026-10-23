<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShoppingCartService
{
    public function forUser(User $user): ShoppingCart
    {
        return $user->cart()->firstOrCreate([]);
    }

    public function add(User $user, ProductVariant $variant, int $quantity): CartItem
    {
        if (! $variant->is_active
            || ! $variant->product->is_active
            || $variant->product->archived_at
            || ($variant->product->category && ! $variant->product->category->is_active)) {
            throw ValidationException::withMessages(['product_variant_id' => 'This item is no longer available.']);
        }

        $cart = $this->forUser($user);

        return DB::transaction(function () use ($cart, $variant, $quantity): CartItem {
            $lockedCart = ShoppingCart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $item = $lockedCart->items()
                ->whereBelongsTo($variant, 'variant')
                ->lockForUpdate()
                ->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;

            if ($newQuantity > $variant->stock) {
                throw ValidationException::withMessages(['quantity' => 'The requested quantity exceeds available stock.']);
            }

            if ($item) {
                $item->update(['quantity' => $newQuantity]);

                return $item;
            }

            return $lockedCart->items()->create([
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ]);
        }, attempts: 3);
    }

    public function contents(User $user): ShoppingCart
    {
        return $this->forUser($user)->load([
            'items.variant.product.category',
        ]);
    }
}
