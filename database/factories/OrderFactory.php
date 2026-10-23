<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.fake()->unique()->bothify('########'),
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'status' => Order::STATUS_PENDING,
            'fulfillment_method' => 'pickup',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => null,
            'notes' => null,
            'subtotal' => '1000.00',
            'total' => '1000.00',
        ];
    }
}
