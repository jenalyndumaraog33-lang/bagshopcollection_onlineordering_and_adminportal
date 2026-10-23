<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold">{{ $category->exists ? 'Edit category' : 'Add category' }}</h1></x-slot>
    <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm">
            @csrf
            @if ($category->exists) @method('PUT') @endif
            @if ($errors->any())<div class="rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <div><label for="name" class="block text-sm font-semibold">Name</label><input id="name" name="name" value="{{ old('name', $category->name) }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label for="description" class="block text-sm font-semibold">Description</label><textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-lg border-gray-300">{{ old('description', $category->description) }}</textarea></div>
            <div class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true)) class="rounded border-gray-300"><label for="is_active" class="text-sm font-semibold">Active category</label></div>
            <div class="flex gap-3"><button class="rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white">Save category</button><a href="{{ route('admin.categories.index') }}" class="rounded-lg border px-5 py-3 font-semibold">Cancel</a></div>
        </form>
    </main>
</x-app-layout>
