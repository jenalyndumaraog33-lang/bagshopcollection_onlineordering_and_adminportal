<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_variants_and_archive_a_product(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Weekend Satchel',
            'category_id' => $category->id,
            'description' => 'A durable everyday bag.',
            'is_active' => '1',
            'variants' => [
                ['name' => 'Caramel', 'sku' => 'SAT-CAR-01', 'price' => '1899.00', 'stock' => 7, 'is_active' => '1'],
                ['name' => 'Black', 'sku' => 'SAT-BLK-01', 'price' => '1999.00', 'stock' => 3, 'is_active' => '1'],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Weekend Satchel')->firstOrFail();
        $this->assertDatabaseCount('product_variants', 2);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'SAT-BLK-01',
            'stock' => 3,
        ]);

        $variants = $product->variants()->get();
        $this->put(route('admin.products.update', $product), [
            'name' => 'Weekend Satchel Updated',
            'category_id' => $category->id,
            'description' => 'Updated bag description.',
            'is_active' => '1',
            'variants' => $variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'is_active' => '1',
            ])->all(),
        ])->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('Weekend Satchel Updated', $product->name);

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
        $this->assertNotNull($product->fresh()->archived_at);
        $this->get('/')->assertDontSee('Weekend Satchel Updated');
    }

    public function test_admin_can_add_edit_and_remove_product_variants(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create();
        $product = Product::factory()->create();
        $retainedVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Black',
            'sku' => 'BAG-BLACK',
            'price' => '1200.00',
            'stock' => 3,
        ]);
        $removedVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Red',
            'sku' => 'BAG-RED',
        ]);
        $cart = ShoppingCart::factory()->for($customer)->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_variant_id' => $removedVariant->id,
        ]);
        $order = Order::factory()->for($customer)->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $removedVariant->id,
            'product_name' => $product->name,
            'variant_name' => 'Red',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'description' => $product->description,
            'is_active' => '1',
            'variants' => [
                [
                    'id' => $retainedVariant->id,
                    'name' => 'Midnight Black',
                    'sku' => 'BAG-BLACK',
                    'price' => '1450.00',
                    'stock' => 8,
                    'is_active' => '1',
                ],
                [
                    'name' => 'Tan',
                    'sku' => 'BAG-TAN',
                    'price' => '1600.00',
                    'stock' => 5,
                    'is_active' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('product_variants', [
            'id' => $retainedVariant->id,
            'name' => 'Midnight Black',
            'price' => '1450.00',
            'stock' => 8,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Tan',
            'sku' => 'BAG-TAN',
            'price' => '1600.00',
            'stock' => 5,
        ]);
        $this->assertDatabaseMissing('product_variants', ['id' => $removedVariant->id]);
        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => $removedVariant->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'product_variant_id' => null,
            'product_name' => $product->name,
            'variant_name' => 'Red',
        ]);
    }

    public function test_customer_cannot_manage_products(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_delivery_requires_an_address_before_order_submission(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'customer_name' => 'Jamie Customer',
            'customer_email' => 'jamie@example.com',
            'customer_phone' => '09171234567',
            'fulfillment_method' => 'delivery',
            'delivery_address' => '',
        ])->assertSessionHasErrors('delivery_address');
    }

    public function test_admin_catalog_order_and_activity_pages_render(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $order = Order::factory()->for($admin)->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
        ]);
        ActivityLog::factory()->for($admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin dashboard');
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.products.edit', $product))->assertOk();
        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.categories.create'))->assertOk();
        $this->get(route('admin.categories.edit', $category))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.orders.show', $order))->assertOk();
        $this->get(route('admin.activity.index'))->assertOk();
    }
}
