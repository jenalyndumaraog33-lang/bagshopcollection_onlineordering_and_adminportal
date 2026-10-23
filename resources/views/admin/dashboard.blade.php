<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="text-sm font-semibold uppercase tracking-widest text-amber-700">Store operations</p><h1 class="mt-1 text-2xl font-semibold text-gray-900">Admin dashboard</h1></div>
            <a href="{{ route('admin.activity.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Activity log</a>
        </div>
    </x-slot>
    <main class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Sales', '₱'.number_format((float) $totalSales, 2)], ['Sales today', '₱'.number_format((float) $todaySales, 2)], ['Pending orders', $pendingOrders], ['Active products', $activeProducts]] as [$label, $value])
                <article class="rounded-2xl bg-white p-6 shadow-sm"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-gray-900">{{ $value }}</p></article>
            @endforeach
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Recent orders</h2><a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-amber-800">All orders</a></div>
                <div class="mt-4 divide-y">
                    @forelse ($recentOrders as $order)
                        <a href="{{ route('admin.orders.show', $order) }}" class="flex justify-between gap-4 py-3">
                            <div><p class="font-medium">{{ $order->order_number }}</p><p class="text-sm text-gray-500">{{ $order->customer_name }}</p></div>
                            <div class="text-right"><p>₱{{ number_format((float) $order->total, 2) }}</p><p class="text-sm capitalize text-amber-800">{{ $order->status }}</p></div>
                        </a>
                    @empty
                        <p class="py-6 text-sm text-gray-500">No orders yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Low stock (5 or fewer)</h2><a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-amber-800">Manage products</a></div>
                <div class="mt-4 divide-y">
                    @forelse ($lowStockVariants as $variant)
                        <div class="flex justify-between gap-4 py-3"><p>{{ $variant->product->name }} · {{ $variant->name }}</p><span class="font-semibold text-red-700">{{ $variant->stock }} left</span></div>
                    @empty
                        <p class="py-6 text-sm text-gray-500">No low-stock variants.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Sales trend · last 7 days</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
                @foreach ($sales as $day)
                    <div class="rounded-xl bg-amber-50 p-4"><p class="text-xs text-gray-600">{{ \Illuminate\Support\Carbon::parse($day->sales_date)->format('D, M j') }}</p><p class="mt-2 font-bold">₱{{ number_format((float) $day->amount, 2) }}</p></div>
                @endforeach
                @if ($sales->isEmpty())<p class="text-sm text-gray-500">Sales data will appear here when orders are completed or paid.</p>@endif
            </div>
        </section>
    </main>
</x-app-layout>
