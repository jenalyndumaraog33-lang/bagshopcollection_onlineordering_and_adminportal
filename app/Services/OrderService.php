<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly ShoppingCartService $shoppingCartService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{customer_name: string, customer_email: string, customer_phone: string, fulfillment_method: string, delivery_address?: ?string, notes?: ?string}  $details
     */
    public function place(User $user, array $details): Order
    {
        return DB::transaction(function () use ($user, $details): Order {
            $cart = $this->shoppingCartService->forUser($user);
            $cart = ShoppingCart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $items = $cart->items()
                ->with('variant.product')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $totalCents = 0;
            $lockedItems = [];

            foreach ($items as $item) {
                $variant = ProductVariant::query()->whereKey($item->product_variant_id)->lockForUpdate()->first();

                if (! $variant || ! $variant->is_active || ! $variant->product->is_active || $variant->product->archived_at
                    || ($variant->product->category && ! $variant->product->category->is_active)) {
                    throw ValidationException::withMessages(['cart' => 'A product in your cart is no longer available.']);
                }

                if ($variant->stock < $item->quantity) {
                    throw ValidationException::withMessages(['cart' => "{$variant->product->name} has insufficient stock."]);
                }

                $unitPriceCents = $this->priceInCents((string) $variant->price);
                $totalCents += $unitPriceCents * $item->quantity;
                $lockedItems[] = [$item, $variant, $unitPriceCents];
            }

            if ($totalCents > 9_999_999_999) {
                throw ValidationException::withMessages(['cart' => 'The total order value exceeds the supported limit.']);
            }

            $total = number_format($totalCents / 100, 2, '.', '');
            $order = $user->orders()->create([
                ...$details,
                'order_number' => 'ORD-'.Str::upper((string) Str::ulid()),
                'status' => Order::STATUS_PENDING,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'subtotal' => $total,
                'total' => $total,
            ]);

            foreach ($lockedItems as [$cartItem, $variant, $unitPriceCents]) {
                $variant->decrement('stock', $cartItem->quantity);
                $lineTotal = number_format(($unitPriceCents * $cartItem->quantity) / 100, 2, '.', '');
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'unit_price' => number_format($unitPriceCents / 100, 2, '.', ''),
                    'quantity' => $cartItem->quantity,
                    'line_total' => $lineTotal,
                ]);
            }

            $cart->items()->delete();
            $this->activityLogger->record($user, 'order.placed', $order, "Placed order {$order->order_number}.");

            return $order->load('items');
        }, attempts: 3);
    }

    private function priceInCents(string $price): int
    {
        [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '00');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    public function changeStatus(Order $order, string $status, User $actor): Order
    {
        return DB::transaction(function () use ($order, $status, $actor): Order {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($status, $lockedOrder->allowedTransitions(), true)) {
                throw ValidationException::withMessages([
                    'status' => "An order cannot move from {$lockedOrder->status} to {$status}.",
                ]);
            }

            if ($status === Order::STATUS_CANCELLED) {
                foreach ($lockedOrder->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::query()->whereKey($item->product_variant_id)->increment('stock', $item->quantity);
                    }
                }
            }

            $lockedOrder->update(['status' => $status]);
            $this->activityLogger->record($actor, 'order.status_changed', $lockedOrder, "Order status changed to {$status}.");

            return $lockedOrder;
        }, attempts: 3);
    }
}
