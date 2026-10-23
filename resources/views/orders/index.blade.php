<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-gray-900">My orders</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
        @foreach ($orders as $order)
            <a href="{{ route('orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm hover:shadow-md">
                <div>
                    <p class="font-semibold text-gray-900">{{ $order->order_number }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $order->created_at->format('M j, Y g:i A') }} · {{ $order->items_count }} items</p>
                </div>
                <div class="text-right">
                    <p class="font-bold">₱{{ number_format((float) $order->total, 2) }}</p>
                    <span class="mt-1 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold capitalize text-amber-900">{{ $order->status }}</span>
                </div>
            </a>
        @endforeach
        @if ($orders->isEmpty())
            <div class="rounded-2xl bg-white p-12 text-center text-gray-600">You have not placed an order yet.</div>
        @endif
        {{ $orders->links() }}
    </main>
</x-app-layout>
