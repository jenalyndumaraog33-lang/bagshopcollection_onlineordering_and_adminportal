<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold">Administrative activity log</h1></x-slot>
    <main class="mx-auto max-w-6xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
        <div class="divide-y rounded-2xl bg-white p-6 shadow-sm">
            @forelse ($activities as $activity)
                <article class="flex flex-wrap justify-between gap-4 py-4 first:pt-0 last:pb-0">
                    <div><p class="font-semibold">{{ $activity->description ?? $activity->action }}</p><p class="mt-1 text-sm text-gray-500">{{ $activity->user?->name ?? 'System' }} · {{ $activity->action }}</p></div>
                    <time class="text-sm text-gray-500">{{ $activity->created_at->format('M j, Y g:i A') }}</time>
                </article>
            @empty
                <p class="text-gray-500">No activity recorded.</p>
            @endforelse
        </div>
        {{ $activities->links() }}
    </main>
</x-app-layout>
