<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ProductCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_issue_and_revoke_a_sanctum_bearer_token(): void
    {
        $user = User::factory()->create(['password' => 'customer-password']);

        $response = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'customer-password',
            'name' => 'Mobile client',
        ])->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id);

        $plainTextToken = $response->json('token');
        $this->assertNotEmpty($plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);

        $this->withToken($plainTextToken)
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'items', 'total']]);
        $this->withToken($plainTextToken)->deleteJson('/api/v1/auth/token')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($plainTextToken)->getJson('/api/v1/cart')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_customer_can_browse_products_but_cannot_access_admin_reports(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'API Bag']);
        ProductVariant::factory()->for($product)->create();
        $token = $this->createBearerToken($user, 'test');

        $this->withToken($token)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Bag']);

        $this->withToken($token)
            ->getJson('/api/v1/admin/reports')
            ->assertForbidden();
    }

    public function test_api_registration_creates_a_customer_account_and_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Customer',
            'email' => 'new-customer@example.com',
            'password' => 'Customer-password-123',
            'password_confirmation' => 'Customer-password-123',
        ])->assertCreated()
            ->assertJsonPath('user.role', User::ROLE_CUSTOMER);

        $this->assertDatabaseHas('users', [
            'email' => 'new-customer@example.com',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_api_password_reset_changes_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create();
        $this->createBearerToken($user, 'existing client');
        $resetToken = Password::createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => $user->email,
            'token' => $resetToken,
            'password' => 'Updated-password-456',
            'password_confirmation' => 'Updated-password-456',
        ])->assertOk();

        $this->assertTrue(Hash::check('Updated-password-456', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_users_cannot_issue_an_api_token(): void
    {
        $user = User::factory()->create([
            'password' => 'customer-password',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'customer-password',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_administrator_can_manage_inventory_orders_and_reports(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $this->createBearerToken($admin, 'admin client');
        $category = Category::factory()->create();

        $this->withToken($token)
            ->postJson('/api/v1/admin/products', [
                'name' => 'API Admin Bag',
                'category_id' => $category->id,
                'description' => 'Managed through the inventory API.',
                'is_active' => true,
                'variants' => [
                    ['name' => 'Small', 'sku' => 'API-ADMIN-01', 'price' => '875.00', 'stock' => 6, 'is_active' => true],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'API Admin Bag');

        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => Order::STATUS_PROCESSING,
        ])->assertOk()->assertJsonPath('data.status', Order::STATUS_PROCESSING);
        $this->getJson('/api/v1/admin/reports')
            ->assertOk()
            ->assertJsonStructure(['sales', 'orders', 'inventory']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PROCESSING,
        ]);
    }

    public function test_admin_can_complete_product_api_crud_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create();
        $productToken = $this->createBearerToken($admin, 'product CRUD client');

        $created = $this->withToken($productToken)
            ->postJson('/api/v1/admin/products', [
                'name' => 'API Canvas Tote',
                'description' => 'A practical canvas tote.',
                'is_active' => true,
                'variants' => [
                    ['name' => 'Natural', 'sku' => 'API-TOTE-NATURAL', 'price' => '1299.00', 'stock' => 12, 'is_active' => true],
                    ['name' => 'Navy', 'sku' => 'API-TOTE-NAVY', 'price' => '1399.00', 'stock' => 6, 'is_active' => true],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'API Canvas Tote')
            ->assertJsonPath('data.variants.0.stock', 12)
            ->assertJsonPath('data.variants.1.sku', 'API-TOTE-NAVY');

        $productId = $created->json('data.id');
        $productSlug = $created->json('data.slug');
        $naturalVariantId = $created->json('data.variants.0.id');
        $navyVariantId = $created->json('data.variants.1.id');
        $product = Product::query()->findOrFail($productId);
        $cart = ShoppingCart::factory()->for($customer)->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_variant_id' => $navyVariantId,
        ]);
        $order = Order::factory()->for($customer)->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $navyVariantId,
            'product_name' => $product->name,
            'variant_name' => 'Navy',
        ]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Canvas Tote']);
        $this->getJson("/api/v1/products/{$productSlug}")
            ->assertOk()
            ->assertJsonPath('data.name', 'API Canvas Tote')
            ->assertJsonPath('data.variants.1.stock', 6);

        $updated = $this->withToken($productToken)
            ->putJson("/api/v1/admin/products/{$productId}", [
                'name' => 'Updated API Canvas Tote',
                'description' => 'A reinforced canvas tote.',
                'is_active' => true,
                'variants' => [
                    [
                        'id' => $naturalVariantId,
                        'name' => 'Reinforced Natural',
                        'sku' => 'API-TOTE-NATURAL',
                        'price' => '1499.00',
                        'stock' => 18,
                        'is_active' => true,
                    ],
                    ['name' => 'Black', 'sku' => 'API-TOTE-BLACK', 'price' => '1599.00', 'stock' => 9, 'is_active' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated API Canvas Tote')
            ->assertJsonPath('data.variants.0.id', $naturalVariantId)
            ->assertJsonPath('data.variants.0.price', '1499.00')
            ->assertJsonPath('data.variants.0.stock', 18);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Updated API Canvas Tote',
            'description' => 'A reinforced canvas tote.',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $naturalVariantId,
            'name' => 'Reinforced Natural',
            'price' => '1499.00',
            'stock' => 18,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'name' => 'Black',
            'sku' => 'API-TOTE-BLACK',
            'stock' => 9,
        ]);
        $this->assertDatabaseMissing('product_variants', ['id' => $navyVariantId]);
        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => $navyVariantId]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'product_variant_id' => null,
            'product_name' => 'API Canvas Tote',
            'variant_name' => 'Navy',
        ]);

        $cartItem = CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_variant_id' => $naturalVariantId,
        ]);
        $historicalOrderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $naturalVariantId,
            'product_name' => 'Updated API Canvas Tote',
            'variant_name' => 'Reinforced Natural',
        ]);

        $this->withToken($productToken)
            ->deleteJson("/api/v1/admin/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('message', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $productId]);
        $this->assertDatabaseMissing('product_variants', ['product_id' => $productId]);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $historicalOrderItem->id,
            'product_variant_id' => null,
            'product_name' => 'Updated API Canvas Tote',
            'variant_name' => 'Reinforced Natural',
        ]);
        $this->withToken($productToken)
            ->getJson("/api/v1/admin/products/{$productId}")
            ->assertNotFound();
        $this->withToken($productToken)
            ->getJson('/api/v1/admin/products')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Updated API Canvas Tote']);
        $this->getJson("/api/v1/products/{$updated->json('data.slug')}")->assertNotFound();
        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Updated API Canvas Tote']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'product.deleted',
            'subject_type' => Product::class,
            'subject_id' => $productId,
        ]);
    }

    public function test_product_api_returns_validation_errors_and_not_found_for_missing_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $this->createBearerToken($admin, 'product validation client');

        $this->withToken($token)
            ->postJson('/api/v1/admin/products', [
                'name' => 'Invalid API Bag',
                'is_active' => true,
                'variants' => [
                    ['name' => 'Invalid Price', 'sku' => 'API-INVALID-PRICE', 'price' => '0', 'stock' => -1, 'is_active' => true],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variants.0.price', 'variants.0.stock']);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('activity_logs', 0);

        $this->getJson('/api/v1/products/nonexistent-api-bag')->assertNotFound();
        $this->withToken($token)
            ->putJson('/api/v1/admin/products/999999', [])
            ->assertNotFound();
        $this->withToken($token)
            ->deleteJson('/api/v1/admin/products/999999')
            ->assertNotFound();
    }

    public function test_product_api_mutations_require_an_admin_bearer_token(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $customerToken = $this->createBearerToken($customer, 'customer client');
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create();
        $payload = [
            'name' => 'Protected API Bag',
            'is_active' => true,
            'variants' => [
                ['name' => 'Black', 'sku' => 'PROTECTED-API-BAG', 'price' => '999.00', 'stock' => 5, 'is_active' => true],
            ],
        ];

        $this->postJson('/api/v1/admin/products', $payload)->assertUnauthorized();
        $this->withToken($customerToken)
            ->postJson('/api/v1/admin/products', $payload)
            ->assertForbidden();
        $this->withToken($customerToken)
            ->putJson("/api/v1/admin/products/{$product->id}", $payload)
            ->assertForbidden();
        $this->withToken($customerToken)
            ->deleteJson("/api/v1/admin/products/{$product->id}")
            ->assertForbidden();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
            'archived_at' => null,
        ]);
    }

    public function test_sanctum_admin_token_authenticates_admin_product_routes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => 'admin-password-123',
        ]);
        $token = $this->postJson('/api/v1/auth/token', [
            'email' => $admin->email,
            'password' => 'admin-password-123',
            'name' => 'admin product client',
        ])
            ->assertCreated()
            ->assertJsonPath('user.role', User::ROLE_ADMIN)
            ->json('token');

        $response = $this->withToken($token)
            ->postJson('/api/v1/admin/products', [
                'name' => 'Sanctum API Bag',
                'is_active' => true,
                'variants' => [
                    ['name' => 'Default', 'sku' => 'SANCTUM-API-001', 'price' => '899.00', 'stock' => 5, 'is_active' => true],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Sanctum API Bag');

        $this->assertDatabaseHas('products', [
            'id' => $response->json('data.id'),
            'name' => 'Sanctum API Bag',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'product.created',
        ]);
    }

    public function test_internal_product_resource_reads_products_and_variants_with_admin_access(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $this->createBearerToken($admin, 'admin product reader');
        $product = Product::factory()->create([
            'name' => 'Inactive Internal API Bag',
            'is_active' => false,
        ]);
        ProductVariant::factory()->for($product)->create([
            'name' => 'Inactive variant',
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/admin/products')->assertUnauthorized();
        $this->getJson("/api/v1/admin/products/{$product->id}")->assertUnauthorized();

        $this->withToken($token)
            ->getJson('/api/v1/admin/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Inactive Internal API Bag')
            ->assertJsonPath('data.0.is_active', false)
            ->assertJsonPath('data.0.variants.0.name', 'Inactive variant')
            ->assertJsonPath('data.0.variants.0.is_active', false);

        $this->withToken($token)
            ->getJson("/api/v1/admin/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Inactive Internal API Bag')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.variants.0.is_active', false);

        $product->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/admin/products')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Inactive Internal API Bag']);
        $this->withToken($token)
            ->getJson("/api/v1/admin/products/{$product->id}")
            ->assertNotFound();
    }

    public function test_admin_can_patch_only_product_fields_without_replacing_variants(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $this->createBearerToken($admin, 'product patch client');
        $product = Product::factory()->create([
            'name' => 'Original API Bag',
            'description' => 'Original description',
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Original variant',
        ]);

        $this->withToken($token)
            ->patchJson("/api/v1/admin/products/{$product->id}", [
                'description' => 'Updated description',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Original API Bag')
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.variants.0.id', $variant->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original API Bag',
            'description' => 'Updated description',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_patch_updates_a_variant_without_changing_its_id_or_removing_other_variants(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $this->createBearerToken($admin, 'variant patch client');
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Black',
            'sku' => 'PATCH-BLACK-001',
            'stock' => 5,
        ]);
        $otherVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Brown',
            'sku' => 'PATCH-BROWN-001',
            'stock' => 3,
        ]);

        $this->withToken($token)
            ->patchJson("/api/v1/admin/products/{$product->id}", [
                'variants' => [
                    [
                        'id' => $variant->id,
                        'name' => 'Black',
                        'sku' => 'PATCH-BLACK-001',
                        'price' => $variant->price,
                        'stock' => 12,
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.variants.0.id', $variant->id)
            ->assertJsonPath('data.variants.0.stock', 12)
            ->assertJsonPath('data.variants.1.id', $otherVariant->id);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock' => 12,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $otherVariant->id,
            'stock' => 3,
        ]);
    }

    public function test_customer_cannot_read_the_internal_product_resource(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $token = $this->createBearerToken($customer, 'customer client');

        $this->withToken($token)
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();
    }

    public function test_product_admin_routes_reject_missing_and_invalid_sanctum_tokens(): void
    {
        $this->getJson('/api/v1/admin/products')->assertUnauthorized();
        $this->withToken('invalid-sanctum-token')
            ->getJson('/api/v1/admin/products')
            ->assertUnauthorized();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_customer_sanctum_token_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $token = $this->createBearerToken($customer, 'customer token');

        $this->withToken($token)
            ->getJson('/api/v1/admin/orders')
            ->assertForbidden();
    }

    public function test_admin_can_access_orders_and_cart_items_owned_by_other_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $firstOrder = Order::factory()->for($customer)->create([
            'order_number' => 'ADMIN-ACCESS-001',
        ]);
        $secondOrder = Order::factory()->for($otherCustomer)->create([
            'order_number' => 'ADMIN-ACCESS-002',
        ]);
        $token = $this->createBearerToken($admin, 'admin ownership access');
        $cart = ShoppingCart::factory()->for($customer)->create();
        $cartItem = CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'quantity' => 1,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonFragment(['order_number' => 'ADMIN-ACCESS-001'])
            ->assertJsonFragment(['order_number' => 'ADMIN-ACCESS-002']);
        $this->getJson("/api/v1/orders/{$firstOrder->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $firstOrder->id);
        $this->getJson('/api/v1/admin/carts')
            ->assertOk()
            ->assertJsonFragment(['id' => $cart->id]);
        $this->getJson("/api/v1/admin/carts/{$cart->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $cart->id)
            ->assertJsonFragment(['product' => $cartItem->variant->product->name]);

        $this->patchJson("/api/v1/cart/items/{$cartItem->id}", ['quantity' => 2])
            ->assertOk();
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);

        $this->deleteJson("/api/v1/cart/items/{$cartItem->id}")
            ->assertOk();
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_customer_cannot_access_another_users_order_or_cart_item(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $owner = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $customerOrder = Order::factory()->for($customer)->create([
            'order_number' => 'CUSTOMER-OWN-ORDER',
        ]);
        $order = Order::factory()->for($owner)->create();
        $token = $this->createBearerToken($customer, 'customer ownership restrictions');
        $cart = ShoppingCart::factory()->for($owner)->create();
        $cartItem = CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'quantity' => 1,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonFragment(['order_number' => 'CUSTOMER-OWN-ORDER'])
            ->assertJsonMissing(['order_number' => $order->order_number]);
        $this->getJson("/api/v1/orders/{$customerOrder->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customerOrder->id);
        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertNotFound();
        $this->patchJson("/api/v1/cart/items/{$cartItem->id}", ['quantity' => 2])
            ->assertNotFound();
        $this->deleteJson("/api/v1/cart/items/{$cartItem->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 1,
        ]);
    }

    private function createBearerToken(User $user, string $name): string
    {
        return $user->createToken($name, ['*'], now()->addDay())->plainTextToken;
    }
}
