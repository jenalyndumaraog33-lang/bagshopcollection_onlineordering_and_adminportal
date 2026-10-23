<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->with('category')->withCount('variants')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $product = new Product;

        return view('admin.products.create', [
            'categories' => Category::query()->orderBy('name')->get(),
            'product' => $product,
            'variantRows' => [[
                'id' => null,
                'name' => '',
                'sku' => '',
                'price' => '',
                'stock' => 0,
                'is_active' => true,
            ]],
        ]);
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $variants = $validated['variants'];
        unset($validated['variants']);
        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $validated['image_path'] = $request->file('image')?->store('products', 'public');

        $product = DB::transaction(function () use ($validated, $variants): Product {
            $product = Product::create($validated);

            foreach ($variants as $variant) {
                $product->variants()->create(collect($variant)->except('id')->all());
            }

            return $product;
        });

        $activityLogger->record($request->user(), 'product.created', $product, "Created product {$product->name}.");

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load('variants');

        return view('admin.products.edit', [
            'categories' => Category::query()->orderBy('name')->get(),
            'product' => $product,
            'variantRows' => $product->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'is_active' => $variant->is_active,
            ])->all(),
        ]);
    }

    public function update(Request $request, Product $product, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);
        $variants = $validated['variants'];
        $variantIds = collect($variants)->pluck('id')->filter()->all();
        unset($validated['variants']);
        $validated['slug'] = $this->uniqueSlug($validated['name'], $product);
        $validated['archived_at'] = $request->boolean('is_active') ? null : ($product->archived_at ?? now());
        $oldImage = $product->image_path;
        $newImage = $request->file('image')?->store('products', 'public');

        if ($newImage) {
            $validated['image_path'] = $newImage;
        }

        DB::transaction(function () use ($product, $validated, $variants, $variantIds): void {
            $product->update($validated);
            $product->variants()->whereNotIn('id', $variantIds)->delete();

            foreach ($variants as $variant) {
                $variantId = $variant['id'] ?? null;
                $variantData = collect($variant)->except('id')->all();

                if ($variantId) {
                    $product->variants()->whereKey($variantId)->firstOrFail()->update($variantData);
                } else {
                    $product->variants()->create($variantData);
                }
            }
        });

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        $activityLogger->record($request->user(), 'product.updated', $product, "Updated product {$product->name}.");

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product, ActivityLogger $activityLogger): RedirectResponse
    {
        $product->update(['is_active' => false, 'archived_at' => now()]);
        $activityLogger->record(request()->user(), 'product.archived', $product, "Archived product {$product->name}.");

        return redirect()->route('admin.products.index')->with('success', 'Product archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['required', 'boolean'],
            'variants' => ['required', 'array', 'min:1'],
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

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $slug = Str::slug($name);
        $query = Product::query()->where('slug', $slug);

        if ($product) {
            $query->whereKeyNot($product->id);
        }

        return $query->exists() ? "{$slug}-".Str::lower(Str::random(6)) : $slug;
    }
}
