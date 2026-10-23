<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold">Edit {{ $product->name }}</h1></x-slot>
    @include('admin.products.form')
</x-app-layout>
