<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->items->map(fn ($item): array => [
            'id' => $item->id,
            'product' => $item->variant->product->name,
            'variant' => $item->variant->name,
            'unit_price' => $item->variant->price,
            'quantity' => $item->quantity,
            'line_total' => number_format((float) $item->variant->price * $item->quantity, 2, '.', ''),
        ]);

        return [
            'id' => $this->id,
            'items' => $items,
            'total' => number_format($items->sum(fn (array $item): float => (float) $item['line_total']), 2, '.', ''),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode(200);
    }
}
