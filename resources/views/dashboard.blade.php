<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @auth
                        @if (Auth::user()->isAdmin())
                            <p class="text-lg font-semibold">Welcome Admin, {{ Auth::user()->name }}!</p>
                        @else
                            <p class="text-lg font-semibold">Welcome, {{ Auth::user()->name }}!</p>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="mt-4">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider text-gray-500">Live Weather</p>
                            <h3 class="mt-1 text-2xl font-semibold text-gray-900">{{ $weather['location'] ?? 'Weather update' }}</h3>
                        </div>
                        <span class="text-3xl" aria-hidden="true">&#9728;</span>
                    </div>

                    @if ($weather)
                        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div>
                                <p class="text-sm text-gray-500">Temperature</p>
                                <p class="mt-1 text-xl font-semibold">{{ $weather['temperature'] }}&deg;C</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Condition</p>
                                <p class="mt-1 text-xl font-semibold">{{ $weather['condition'] }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Humidity</p>
                                <p class="mt-1 text-xl font-semibold">{{ $weather['humidity'] }}%</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Wind speed</p>
                                <p class="mt-1 text-xl font-semibold">{{ $weather['wind_speed'] }} km/h</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 text-gray-600">Weather information is currently unavailable. Please try again later.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
