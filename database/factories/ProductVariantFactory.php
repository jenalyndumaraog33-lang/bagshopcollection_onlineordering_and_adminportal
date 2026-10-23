<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Black', 'Brown', 'Mini']),
            'sku' => fake()->unique()->bothify('BAG-####'),
            'price' => fake()->randomFloat(2, 300, 5000),
            'stock' => fake()->numberBetween(0, 50),
            'options' => [],
            'is_active' => true,
        ];
    }
}
