<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->available()
            ->with(['category', 'activeVariants'])
            ->when($request->filled('category'), function ($query) use ($request): void {
                $query->whereHas('category', fn ($categories) => $categories->where('slug', $request->string('category')));
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function ($products) use ($search): void {
                    $products->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.index', [
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active && ! $product->archived_at, 404);
        $product->load(['category', 'activeVariants']);

        return view('storefront.show', ['product' => $product]);
    }
}
