<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_browse_products_and_place_an_order(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Everyday bags']);
        $product = Product::factory()->for($category)->create([
            'name' => 'Classic Tote',
            'slug' => 'classic-tote',
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Black',
            'price' => '1250.00',
            'stock' => 5,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Classic Tote')
            ->assertSee('Everyday bags');

        $this->actingAs($user)->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Jamie Customer',
            'customer_email' => 'jamie@example.com',
            'customer_phone' => '09171234567',
            'fulfillment_method' => 'pickup',
        ]);
        $order = Order::query()->whereBelongsTo($user)->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash',
            'total' => '2500.00',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Classic Tote',
            'variant_name' => 'Black',
            'quantity' => 2,
            'line_total' => '2500.00',
        ]);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_rejects_unavailable_stock_without_creating_an_order(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->create(['stock' => 4]);

        $this->actingAs($user)->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertRedirect(route('cart.index'));

        $variant->update(['stock' => 1]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Jamie Customer',
            'customer_email' => 'jamie@example.com',
            'customer_phone' => '09171234567',
            'fulfillment_method' => 'pickup',
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()->create();
        $otherCustomerOrder = Order::factory()->create();

        $this->actingAs($customer)
            ->get(route('orders.show', $otherCustomerOrder))
            ->assertNotFound();
    }

    public function test_customer_can_view_cart_checkout_and_order_history_pages(): void
    {
        $customer = User::factory()->create();
        $variant = ProductVariant::factory()->create(['stock' => 6]);
        $cart = $customer->cart()->create();
        $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $order = Order::factory()->for($customer)->create();

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($variant->product->name);
        $this->get(route('checkout.create'))->assertOk();
        $this->get(route('orders.index'))->assertOk();
        $this->get(route('orders.show', $order))->assertOk();
    }

    public function test_cancelling_an_order_restores_reserved_inventory(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $variant = ProductVariant::factory()->create(['stock' => 4]);

        $this->actingAs($customer)->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $this->post(route('checkout.store'), [
            'customer_name' => 'Jamie Customer',
            'customer_email' => 'jamie@example.com',
            'customer_phone' => '09171234567',
            'fulfillment_method' => 'pickup',
        ])->assertSessionHasNoErrors();
        $order = Order::query()->whereBelongsTo($customer)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_PROCESSING])
            ->assertRedirect(route('admin.orders.show', $order));
        $this->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_CANCELLED])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_admin_can_complete_pending_or_processing_orders_directly(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $pendingOrder = Order::factory()->for($admin)->create(['status' => Order::STATUS_PENDING]);
        $processingOrder = Order::factory()->for($admin)->create(['status' => Order::STATUS_PROCESSING]);

        foreach ([$pendingOrder, $processingOrder] as $order) {
            $this->actingAs($admin)
                ->get(route('admin.orders.show', $order))
                ->assertSee('<option value="completed">Completed</option>', false);

            $this->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_COMPLETED])
                ->assertRedirect(route('admin.orders.show', $order))
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => Order::STATUS_COMPLETED,
            ]);
        }

        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'order.status_changed',
            'subject_type' => Order::class,
            'subject_id' => $pendingOrder->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'order.status_changed',
            'subject_type' => Order::class,
            'subject_id' => $processingOrder->id,
        ]);
    }
}
