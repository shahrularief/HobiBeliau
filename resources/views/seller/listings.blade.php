<x-app-layout><x-slot name="header"><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">My card listings</h2></x-slot>
<div class="max-w-5xl mx-auto p-6 text-gray-800 dark:text-gray-200">
<div class="flex flex-wrap gap-4 mb-6"><a href="{{ route('seller.listings.create') }}" class="bg-amber-500 text-gray-900 px-4 py-3 rounded-lg">Add a card</a><a href="{{ route('marketplace') }}" class="py-3 underline">Browse marketplace</a></div>
@if(session('status'))<p class="mb-4">{{ session('status') }}</p>@endif
@forelse($listings as $listing)<div class="bg-white dark:bg-gray-800 rounded-lg border p-5 mb-4 flex flex-wrap justify-between gap-4"><div><h3 class="font-semibold">{{ $listing->title }}</h3><p class="mt-2">RM {{ $listing->price }} · {{ $listing->quantity }} cards · {{ ucfirst($listing->status) }}</p></div><a href="{{ route('seller.listings.edit', $listing) }}" class="underline">Edit listing</a></div>@empty<p>You have no listings yet. Add your first card to get started.</p>@endforelse
<p class="mt-4 text-sm">Listings hidden by an administrator stay hidden when edited. Contact the administrator if you need a review.</p>
{{ $listings->links() }}</div></x-app-layout>
