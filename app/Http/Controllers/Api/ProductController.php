<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $isAdminEndpoint = $request->routeIs('api.v1.admin.products.index');
        $products = Product::query()
            ->when(! $isAdminEndpoint, fn ($query) => $query->available())
            ->when($isAdminEndpoint, fn ($query) => $query->whereNull('archived_at'))
            ->with(['category', $isAdminEndpoint ? 'variants' : 'activeVariants'])
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category',
                fn ($categories) => $categories->where('slug', $request->string('category'))
            ))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $isAdminEndpoint = $request->routeIs('api.v1.admin.products.show');

        abort_if($product->archived_at, 404);

        if (! $isAdminEndpoint) {
            abort_unless($product->is_active, 404);
        }

        return ProductResource::make($product->load([
            'category',
            $isAdminEndpoint ? 'variants' : 'activeVariants',
        ]));
    }

    public function store(Request $request, ActivityLogger $activityLogger): JsonResponse
    {
        $validated = $this->validatedProduct($request);
        $variants = $validated['variants'];
        unset($validated['variants']);
        $validated['slug'] = Str::slug($validated['name']).'-'.Str::lower(Str::random(6));
        $validated['image_path'] = $request->file('image')?->store('products', 'public');
        $product = DB::transaction(function () use ($validated, $variants): Product {
            $product = Product::create($validated);

            foreach ($variants as $variant) {
                $product->variants()->create(collect($variant)->except('id')->all());
            }

            return $product;
        });
        $activityLogger->record($request->user(), 'product.created', $product, "Created product {$product->name} via API.");

        return ProductResource::make($product->load(['category', 'variants']))->response()->setStatusCode(201);
    }

    public function update(Request $request, Product $product, ActivityLogger $activityLogger): ProductResource
    {
        $validated = $this->validatedProduct($request, $product);
        $variants = $validated['variants'] ?? null;
        $variantIds = collect($variants ?? [])->pluck('id')->filter()->all();
        unset($validated['variants']);

        if (array_key_exists('name', $validated)) {
            $validated['slug'] = Str::slug($validated['name']).'-'.Str::lower(Str::random(6));
        }

        if (array_key_exists('is_active', $validated)) {
            $validated['archived_at'] = $request->boolean('is_active') ? null : ($product->archived_at ?? now());
        }

        $newImage = $request->file('image')?->store('products', 'public');
        $oldImage = $product->image_path;

        if ($newImage) {
            $validated['image_path'] = $newImage;
        }

        DB::transaction(function () use ($request, $product, $validated, $variants, $variantIds): void {
            $product->update($validated);

            if ($variants !== null) {
                if (! $request->isMethod('PATCH')) {
                    $product->variants()->whereNotIn('id', $variantIds)->delete();
                }

                foreach ($variants as $variant) {
                    $id = $variant['id'] ?? null;
                    $data = collect($variant)->except('id')->all();

                    if ($id) {
                        $product->variants()->whereKey($id)->firstOrFail()->update($data);
                    } else {
                        $product->variants()->create($data);
                    }
                }
            }
        });

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        $activityLogger->record($request->user(), 'product.updated', $product, "Updated product {$product->name} via API.");

        return ProductResource::make($product->load(['category', 'variants']));
    }

    public function destroy(Product $product, ActivityLogger $activityLogger): JsonResponse
    {
        $productName = $product->name;
        $imagePath = $product->image_path;

        DB::transaction(function () use ($product, $productName, $activityLogger): void {
            $activityLogger->record(
                request()->user(),
                'product.deleted',
                $product,
                "Deleted product {$productName} via API.",
            );
            $product->delete();
        });

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $isPartialUpdate = $request->isMethod('PATCH');

        $rules = [
            'name' => [$isPartialUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => [$isPartialUpdate ? 'sometimes' : 'required', 'boolean'],
            'variants' => [$isPartialUpdate ? 'sometimes' : 'required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:120'],
            'variants.*.sku' => ['nullable', 'string', 'max:80', 'distinct'],
            'variants.*.price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.is_active' => ['required', 'boolean'],
        ];

        foreach ($request->input('variants', []) as $index => $variant) {
            $skuRule = Rule::unique('product_variants', 'sku');

            if (isset($variant['id'])) {
                $skuRule->ignore($variant['id']);
                $rules["variants.{$index}.id"][] = Rule::exists('product_variants', 'id')
                    ->where('product_id', $product?->id ?? 0);
            }

            $rules["variants.{$index}.sku"][] = $skuRule;
        }

        return $request->validate($rules);
    }
}
