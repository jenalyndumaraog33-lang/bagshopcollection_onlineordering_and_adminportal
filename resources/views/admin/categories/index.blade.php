<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4"><h1 class="text-2xl font-semibold">Categories</h1><a href="{{ route('admin.categories.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 font-semibold text-white">Add category</a></div>
    </x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))<div class="rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="rounded-lg bg-red-50 px-4 py-3 text-red-800">{{ session('error') }}</div>@endif
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-4">Category</th><th class="p-4">Products</th><th class="p-4">Status</th><th class="p-4">Actions</th></tr></thead>
                <tbody class="divide-y">
                    @forelse ($categories as $category)
                        <tr><td class="p-4"><p class="font-semibold">{{ $category->name }}</p><p class="text-xs text-gray-500">{{ $category->description }}</p></td><td class="p-4">{{ $category->products_count }}</td><td class="p-4">{{ $category->is_active ? 'Active' : 'Inactive' }}</td><td class="p-4"><div class="flex gap-2"><a href="{{ route('admin.categories.edit', $category) }}" class="rounded border px-3 py-2 font-semibold">Edit</a><form method="POST" action="{{ route('admin.categories.destroy', $category) }}">@csrf @method('DELETE')<button class="rounded px-3 py-2 font-semibold text-red-700">Delete</button></form></div></td></tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $categories->links() }}
    </main>
</x-app-layout>
