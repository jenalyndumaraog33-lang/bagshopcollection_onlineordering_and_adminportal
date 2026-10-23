<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-gray-900">Checkout</h1></x-slot>
    <main class="mx-auto grid max-w-6xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
        <form method="POST" action="{{ route('checkout.store') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm">
            @csrf
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-red-800">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            <div>
                <label for="customer_name" class="block text-sm font-semibold">Full name</label>
                <input id="customer_name" name="customer_name" required value="{{ old('customer_name', auth()->user()->name) }}" class="mt-1 w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label for="customer_email" class="block text-sm font-semibold">Email</label>
                <input id="customer_email" name="customer_email" type="email" required value="{{ old('customer_email', auth()->user()->email) }}" class="mt-1 w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label for="customer_phone" class="block text-sm font-semibold">Phone</label>
                <input id="customer_phone" name="customer_phone" required value="{{ old('customer_phone') }}" class="mt-1 w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label for="fulfillment_method" class="block text-sm font-semibold">Fulfillment</label>
                <select id="fulfillment_method" name="fulfillment_method" required class="mt-1 w-full rounded-lg border-gray-300">
                    <option value="pickup" @selected(old('fulfillment_method') === 'pickup')>Store pickup</option>
                    <option value="delivery" @selected(old('fulfillment_method') === 'delivery')>Delivery</option>
                </select>
            </div>
            <div>
                <label for="delivery_address" class="block text-sm font-semibold">Delivery address (required for delivery)</label>
                <textarea id="delivery_address" name="delivery_address" rows="3" class="mt-1 w-full rounded-lg border-gray-300">{{ old('delivery_address') }}</textarea>
            </div>
            <div>
                <label for="notes" class="block text-sm font-semibold">Order notes (optional)</label>
                <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea>
            </div>
            <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900">Payment is confirmed manually with the store. No online payment is collected.</p>
            <button class="w-full rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700">Place order</button>
        </form>

        <aside class="h-fit space-y-4 rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Order summary</h2>
            @foreach ($cart->items as $item)
                <div class="flex justify-between gap-4 text-sm">
                    <span>{{ $item->variant->product->name }} · {{ $item->variant->name }} × {{ $item->quantity }}</span>
                    <span>₱{{ number_format((float) $item->variant->price * $item->quantity, 2) }}</span>
                </div>
            @endforeach
            <div class="border-t pt-4 text-right text-lg font-bold">
                ₱{{ number_format($cart->items->sum(fn ($item) => (float) $item->variant->price * $item->quantity), 2) }}
            </div>
        </aside>
    </main>
</x-app-layout>
