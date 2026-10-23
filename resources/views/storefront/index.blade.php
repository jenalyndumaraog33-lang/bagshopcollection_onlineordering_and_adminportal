<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Dumaraog Bag Collection</p>
                <h1 class="mt-2 text-3xl font-semibold text-gray-900">Bags for every day</h1>
            </div>
            @auth
                <a href="{{ route('cart.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">View cart</a>
            @endauth
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('storefront.index') }}" class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm sm:grid-cols-[1fr_14rem_auto]">
            <label class="sr-only" for="search">Search bags</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Search bags..." class="rounded-lg border-gray-300">
            <label class="sr-only" for="category">Category</label>
            <select id="category" name="category" class="rounded-lg border-gray-300">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-gray-900 px-5 py-2 font-semibold text-white hover:bg-gray-700">Search</button>
        </form>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif

        <section class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($products as $product)
                <article class="overflow-hidden rounded-2xl bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <a href="{{ route('storefront.show', $product->slug) }}" class="block">
                        @if ($product->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="h-64 w-full object-cover">
                        @else
                            <div class="flex h-64 items-center justify-center bg-gradient-to-br from-amber-100 to-stone-200 text-6xl" aria-hidden="true">👜</div>
                        @endif
                        <div class="space-y-2 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">{{ $product->category?->name ?? 'Collection' }}</p>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h2>
                            <p class="line-clamp-2 min-h-10 text-sm text-gray-600">{{ $product->description }}</p>
                            @if ($product->activeVariants->isNotEmpty())
                                <p class="pt-2 text-base font-bold text-gray-900">
                                    From ₱{{ number_format((float) $product->activeVariants->min('price'), 2) }}
                                </p>
                            @endif
                        </div>
                    </a>
                </article>
            @empty
                <div class="col-span-full rounded-2xl bg-white p-12 text-center text-gray-600">No bags match your search yet.</div>
            @endforelse
        </section>

        <div>{{ $products->links() }}</div>
    </main>
</x-app-layout>
