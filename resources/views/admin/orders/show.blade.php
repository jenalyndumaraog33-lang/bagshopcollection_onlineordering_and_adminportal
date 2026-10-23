<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4"><h1 class="text-2xl font-semibold">Order {{ $order->order_number }}</h1><span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold capitalize">{{ $order->status }}</span></div>
    </x-slot>
    <main class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
        <section class="space-y-6">
            @if (session('success'))<div class="rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Items</h2><div class="mt-3 divide-y">
                    @foreach ($order->items as $item)<div class="flex justify-between gap-4 py-3"><p>{{ $item->product_name }} · {{ $item->variant_name }} × {{ $item->quantity }}</p><p>₱{{ number_format((float) $item->line_total, 2) }}</p></div>@endforeach
                </div>
                <div class="flex justify-between border-t pt-4 font-bold"><span>Total</span><span>₱{{ number_format((float) $order->total, 2) }}</span></div>
            </div>
            <div class="grid gap-4 rounded-2xl bg-white p-6 shadow-sm sm:grid-cols-2">
                <div><p class="text-sm text-gray-500">Customer</p><p class="mt-1 font-medium">{{ $order->customer_name }}</p><p>{{ $order->customer_email }}</p><p>{{ $order->customer_phone }}</p></div>
                <div><p class="text-sm text-gray-500">Fulfillment</p><p class="mt-1 capitalize">{{ $order->fulfillment_method }}</p><p>{{ $order->delivery_address }}</p></div>
                @if ($order->notes)<div class="sm:col-span-2"><p class="text-sm text-gray-500">Notes</p><p>{{ $order->notes }}</p></div>@endif
            </div>
        </section>
        <aside class="h-fit rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Update order</h2>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-5 space-y-5">
                @csrf @method('PATCH')
                <div><label for="status" class="block text-sm font-semibold">Next status</label><select id="status" name="status" class="mt-1 w-full rounded-lg border-gray-300"><option value="">Keep {{ ucfirst($order->status) }}</option>@foreach ($order->allowedTransitions() as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select></div>
                <div><label for="payment_status" class="block text-sm font-semibold">Payment</label><select id="payment_status" name="payment_status" class="mt-1 w-full rounded-lg border-gray-300"><option value="">Keep {{ ucfirst($order->payment_status) }}</option><option value="pending">Pending</option><option value="paid">Paid</option></select></div>
                <button class="w-full rounded-lg bg-gray-900 px-4 py-3 font-semibold text-white">Save update</button>
            </form>
            <p class="mt-4 text-xs text-gray-500">Status transitions follow the order workflow. Cancelling a reserved order returns its stock.</p>
        </aside>
    </main>
</x-app-layout>
