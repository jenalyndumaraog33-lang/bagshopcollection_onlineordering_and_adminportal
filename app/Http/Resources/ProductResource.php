<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdminEndpoint = $request->routeIs('api.v1.admin.products.*');
        $variantRelationship = $isAdminEndpoint ? 'variants' : 'activeVariants';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'is_active' => $this->when($isAdminEndpoint, $this->is_active),
            'archived_at' => $this->when($isAdminEndpoint, $this->archived_at),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'variants' => $this->whenLoaded($variantRelationship, function () use ($isAdminEndpoint, $variantRelationship): array {
                return $this->resource->getRelation($variantRelationship)
                    ->map(function ($variant) use ($isAdminEndpoint): array {
                        $data = [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'sku' => $variant->sku,
                            'price' => $variant->price,
                            'stock' => $variant->stock,
                            'options' => $variant->options,
                        ];

                        if ($isAdminEndpoint) {
                            $data['is_active'] = $variant->is_active;
                        }

                        return $data;
                    })
                    ->all();
            }),
        ];
    }
}
