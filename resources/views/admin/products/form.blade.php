<main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm" x-data="{ variants: @js(old('variants', $variantRows)) }">
        @csrf
        @if ($product->exists) @method('PUT') @endif
        @if ($errors->any())<div class="rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="name" class="block text-sm font-semibold">Product name</label><input id="name" name="name" value="{{ old('name', $product->name) }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label for="category_id" class="block text-sm font-semibold">Category</label><select id="category_id" name="category_id" class="mt-1 w-full rounded-lg border-gray-300"><option value="">Uncategorized</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        </div>
        <div><label for="description" class="block text-sm font-semibold">Description</label><textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-lg border-gray-300">{{ old('description', $product->description) }}</textarea></div>
        <div><label for="image" class="block text-sm font-semibold">Product image (JPG, PNG, WebP; up to 5 MB)</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm"></div>
        @if ($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="h-40 rounded-xl object-cover">@endif
        <div class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true)) class="rounded border-gray-300"><label for="is_active" class="text-sm font-semibold">Available in the store</label></div>

        <section class="space-y-4 border-t pt-6">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold">Product variants</h2><p class="text-sm text-gray-500">Set the style, SKU, price, and stock for each variation.</p></div><button type="button" @click="variants.push({id: null, name: '', sku: '', price: '', stock: 0, is_active: true})" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Add variant</button></div>
            <template x-for="(variant, index) in variants" :key="index">
                <div class="grid gap-4 rounded-xl border border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-5">
                    <input type="hidden" :name="`variants[${index}][id]`" x-model="variant.id">
                    <div><label class="block text-xs font-semibold">Style</label><input :name="`variants[${index}][name]`" x-model="variant.name" required class="mt-1 w-full rounded-lg border-gray-300"></div>
                    <div><label class="block text-xs font-semibold">SKU</label><input :name="`variants[${index}][sku]`" x-model="variant.sku" class="mt-1 w-full rounded-lg border-gray-300"></div>
                    <div><label class="block text-xs font-semibold">Price (₱)</label><input :name="`variants[${index}][price]`" x-model="variant.price" type="number" min="0.01" step="0.01" required class="mt-1 w-full rounded-lg border-gray-300"></div>
                    <div><label class="block text-xs font-semibold">Stock</label><input :name="`variants[${index}][stock]`" x-model="variant.stock" type="number" min="0" required class="mt-1 w-full rounded-lg border-gray-300"></div>
                    <div class="flex items-end gap-2"><label class="flex items-center gap-2 text-sm"><input type="hidden" :name="`variants[${index}][is_active]`" value="0"><input :name="`variants[${index}][is_active]`" type="checkbox" value="1" x-model="variant.is_active" class="rounded border-gray-300"> Active</label><button type="button" @click="variants.splice(index, 1)" class="rounded px-2 py-1 text-sm text-red-700" :disabled="variants.length < 2">Remove</button></div>
                </div>
            </template>
        </section>
        <div class="flex gap-3"><button class="rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white">Save product</button><a href="{{ route('admin.products.index') }}" class="rounded-lg border px-5 py-3 font-semibold">Cancel</a></div>
    </form>
</main>
