<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('storefront.index') }}" class="text-sm font-medium text-amber-800 hover:text-amber-950">← Back to collection</a>
    </x-slot>

    <main class="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            @if ($product->image_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="min-h-96 w-full object-cover">
            @else
                <div class="flex min-h-96 items-center justify-center bg-gradient-to-br from-amber-100 to-stone-200 text-8xl" aria-hidden="true">👜</div>
            @endif
        </div>

        <section class="space-y-6">
            <div>
                <p class="font-semibold uppercase tracking-[0.2em] text-amber-700">{{ $product->category?->name ?? 'Collection' }}</p>
                <h1 class="mt-2 text-4xl font-semibold text-gray-900">{{ $product->name }}</h1>
                <p class="mt-4 whitespace-pre-line text-gray-600">{{ $product->description }}</p>
            </div>

            @if ($product->activeVariants->isNotEmpty())
                @auth
                    <form method="POST" action="{{ route('cart.items.store') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm">
                        @csrf
                        <label for="product_variant_id" class="block text-sm font-semibold text-gray-800">Choose a style</label>
                        <select id="product_variant_id" name="product_variant_id" required class="w-full rounded-lg border-gray-300">
                            @foreach ($product->activeVariants as $variant)
                                <option value="{{ $variant->id }}" @disabled($variant->stock < 1)>
                                    {{ $variant->name }} · ₱{{ number_format((float) $variant->price, 2) }} · {{ $variant->stock > 0 ? $variant->stock.' in stock' : 'Out of stock' }}
                                </option>
                            @endforeach
                        </select>
                        <label for="quantity" class="block text-sm font-semibold text-gray-800">Quantity</label>
                        <input id="quantity" name="quantity" type="number" value="1" min="1" max="99" required class="w-28 rounded-lg border-gray-300">
                        @error('product_variant_id')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        @error('quantity')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        <button class="w-full rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700">Add to cart</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white">Log in to order</a>
                @endauth
            @else
                <p class="rounded-xl bg-amber-50 p-4 text-amber-900">This item is currently unavailable.</p>
            @endif
        </section>
    </main>
</x-app-layout>
