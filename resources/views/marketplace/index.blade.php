@extends('layouts.marketplace')
@section('content')
<div class="bg-gray-900 text-white rounded-2xl p-8 sm:p-12"><p class="text-amber-400 text-sm font-semibold">MAKE ROOM FOR YOUR NEXT FIND</p><h1 class="text-3xl sm:text-5xl font-bold mt-4">Your next card belongs here.</h1><p class="mt-5 text-gray-300 max-w-xl">Discover cards from approved sellers, or give your own collection a new home.</p><a href="{{ route('seller.apply') }}" class="inline-block mt-6 bg-amber-500 text-gray-900 rounded-lg px-5 py-3 font-semibold">Become a seller</a></div>
<form method="GET" class="mt-8 flex flex-wrap gap-3">
    <x-validation-errors class="w-full" />
    <label class="flex-1 min-w-48"><span class="block text-sm mb-1">Card or set</span><input name="q" value="{{ request('q') }}" placeholder="Search your next find" class="rounded-lg border-gray-300 w-full"></label>
    <label><span class="block text-sm mb-1">Game</span><select name="game" class="rounded-lg border-gray-300"><option value="">All games</option>@foreach(\App\Models\Listing::GAMES as $game)<option @selected(request('game') === $game)>{{ $game }}</option>@endforeach</select></label>
    <label><span class="block text-sm mb-1">Condition</span><select name="condition" class="rounded-lg border-gray-300"><option value="">All conditions</option>@foreach(\App\Models\Listing::CONDITIONS as $condition)<option @selected(request('condition') === $condition)>{{ $condition }}</option>@endforeach</select></label>
    @foreach(['min_price' => 'Min price (RM)', 'max_price' => 'Max price (RM)'] as $field => $label)<label><span class="block text-sm mb-1">{{ $label }}</span><input name="{{ $field }}" type="number" min="0" step="0.01" value="{{ request($field) }}" class="w-36 rounded-lg border-gray-300"></label>@endforeach
    <label><span class="block text-sm mb-1">Sort by</span><select name="sort" class="rounded-lg border-gray-300">@foreach(['newest' => 'Newest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'] as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>{{ $label }}</option>@endforeach</select></label>
    <button class="self-end bg-gray-900 text-white px-5 py-3 rounded-lg">Search</button><a href="{{ route('marketplace') }}" class="self-end px-3 py-3 underline">Reset</a>
</form>
@include('marketplace.grid')
@endsection
