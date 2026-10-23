<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-gray-900">Your shopping cart</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))<div class="rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 px-4 py-3 text-red-800">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        @forelse ($cart->items as $item)
            <article class="flex flex-wrap items-center justify-between gap-5 rounded-2xl bg-white p-5 shadow-sm">
                <div class="min-w-48">
                    <h2 class="font-semibold text-gray-900">{{ $item->variant->product->name }}</h2>
                    <p class="text-sm text-gray-600">{{ $item->variant->name }} · ₱{{ number_format((float) $item->variant->price, 2) }} each</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $item->variant->stock }} available</p>
                </div>
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <label for="quantity-{{ $item->id }}" class="sr-only">Quantity</label>
                        <input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="99" value="{{ $item->quantity }}" class="w-20 rounded-lg border-gray-300">
                        <button class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold">Update</button>
                    </form>
                    <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                        @csrf @method('DELETE')
                        <button class="rounded-lg px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Remove</button>
                    </form>
                </div>
                <p class="w-full text-right font-bold text-gray-900 sm:w-auto">₱{{ number_format((float) $item->variant->price * $item->quantity, 2) }}</p>
            </article>
        @empty
            <div class="rounded-2xl bg-white p-12 text-center shadow-sm">
                <p class="text-gray-600">Your cart is empty.</p>
                <a href="{{ route('storefront.index') }}" class="mt-4 inline-flex rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white">Browse bags</a>
            </div>
        @endforelse

        @if ($cart->items->isNotEmpty())
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-gray-900 p-6 text-white">
                <p class="text-lg font-semibold">Estimated total:
                    ₱{{ number_format($cart->items->sum(fn ($item) => (float) $item->variant->price * $item->quantity), 2) }}
                </p>
                <a href="{{ route('checkout.create') }}" class="rounded-lg bg-amber-400 px-5 py-3 font-semibold text-gray-950 hover:bg-amber-300">Proceed to checkout</a>
            </div>
        @endif
    </main>
</x-app-layout>
