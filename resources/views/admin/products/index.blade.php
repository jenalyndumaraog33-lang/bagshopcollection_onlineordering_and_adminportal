<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4"><h1 class="text-2xl font-semibold">Products and inventory</h1><a href="{{ route('admin.products.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 font-semibold text-white">Add product</a></div>
    </x-slot>
    <main class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))<div class="rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr><th class="p-4">Product</th><th class="p-4">Category</th><th class="p-4">Variants</th><th class="p-4">Status</th><th class="p-4">Actions</th></tr></thead>
                <tbody class="divide-y">
                    @forelse ($products as $product)
                        <tr><td class="p-4 font-semibold">{{ $product->name }}</td><td class="p-4">{{ $product->category?->name ?? '—' }}</td><td class="p-4">{{ $product->variants_count }}</td><td class="p-4">{{ $product->is_active && ! $product->archived_at ? 'Active' : 'Archived' }}</td>
                            <td class="p-4"><div class="flex items-center gap-2"><a href="{{ route('admin.products.edit', $product) }}" class="rounded-md border px-3 py-2 font-semibold">Edit</a>@if ($product->is_active && ! $product->archived_at)<form method="POST" action="{{ route('admin.products.destroy', $product) }}">@csrf @method('DELETE')<button class="rounded-md px-3 py-2 font-semibold text-red-700" onclick="return confirm('Archive this product?')">Archive</button></form>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">No products have been added.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
    </main>
</x-app-layout>
