<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4"><h1 class="text-2xl font-semibold">Customer orders</h1><a href="{{ route('admin.dashboard') }}" class="rounded-lg border px-4 py-2 font-semibold">Dashboard</a></div>
    </x-slot>
    <main class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" class="flex flex-wrap gap-3"><select name="status" class="rounded-lg border-gray-300"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="rounded-lg bg-gray-900 px-4 py-2 font-semibold text-white">Filter</button></form>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
            <table class="w-full min-w-[50rem] text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-4">Order</th><th class="p-4">Customer</th><th class="p-4">Status</th><th class="p-4">Fulfillment</th><th class="p-4">Total</th><th class="p-4">Placed</th><th class="p-4"></th></tr></thead>
                <tbody class="divide-y">
                    @forelse ($orders as $order)
                        <tr><td class="p-4 font-semibold">{{ $order->order_number }}</td><td class="p-4">{{ $order->customer_name }}<br><span class="text-gray-500">{{ $order->customer_email }}</span></td><td class="p-4 capitalize">{{ $order->status }}</td><td class="p-4 capitalize">{{ $order->fulfillment_method }}</td><td class="p-4">₱{{ number_format((float) $order->total, 2) }}</td><td class="p-4">{{ $order->created_at->format('M j, Y') }}</td><td class="p-4"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-amber-800">Manage</a></td></tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-500">No orders match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </main>
</x-app-layout>
