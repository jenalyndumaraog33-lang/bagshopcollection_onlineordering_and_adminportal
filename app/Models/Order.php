<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'fulfillment_method',
        'payment_method',
        'payment_status',
        'delivery_address',
        'notes',
        'subtotal',
        'total',
    ];

    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_READY,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public function scopeSales(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED)
            ->where(function (Builder $orders): void {
                $orders->where('status', self::STATUS_COMPLETED)
                    ->orWhere('payment_status', 'paid');
            });
    }

    public function allowedTransitions(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_READY, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_READY => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            default => [],
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
