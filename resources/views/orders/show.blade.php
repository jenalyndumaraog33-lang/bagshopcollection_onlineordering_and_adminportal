<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold text-gray-900">Order {{ $order->order_number }}</h1>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold capitalize text-amber-900">{{ $order->status }}</span>
        </div>
    </x-slot>
    <main class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))<div class="rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Items</h2>
            <div class="mt-4 divide-y">
                @foreach ($order->items as $item)
                    <div class="flex justify-between gap-4 py-3">
                        <p>{{ $item->product_name }} · {{ $item->variant_name }} × {{ $item->quantity }}</p>
                        <p class="font-semibold">₱{{ number_format((float) $item->line_total, 2) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-between border-t pt-4 text-lg font-bold">
                <span>Total</span><span>₱{{ number_format((float) $order->total, 2) }}</span>
            </div>
        </section>
        <section class="grid gap-4 rounded-2xl bg-white p-6 shadow-sm sm:grid-cols-2">
            <div><p class="text-sm text-gray-500">Fulfillment</p><p class="mt-1 font-medium capitalize">{{ $order->fulfillment_method }}</p></div>
            <div><p class="text-sm text-gray-500">Payment</p><p class="mt-1 font-medium capitalize">{{ $order->payment_method }} · {{ $order->payment_status }}</p></div>
            @if ($order->delivery_address)
                <div class="sm:col-span-2"><p class="text-sm text-gray-500">Delivery address</p><p class="mt-1">{{ $order->delivery_address }}</p></div>
            @endif
            <div><p class="text-sm text-gray-500">Contact</p><p class="mt-1">{{ $order->customer_name }} · {{ $order->customer_phone }}</p></div>
            @if ($order->notes)<div class="sm:col-span-2"><p class="text-sm text-gray-500">Notes</p><p class="mt-1">{{ $order->notes }}</p></div>@endif
        </section>
        <a href="{{ route('orders.index') }}" class="inline-flex font-semibold text-amber-800">← Back to my orders</a>
    </main>
</x-app-layout>
