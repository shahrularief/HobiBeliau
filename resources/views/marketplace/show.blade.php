@extends('layouts.marketplace')
@section('content')
@if(session('report_status'))<p class="mt-4 p-4 bg-amber-50 rounded-lg">{{ session('report_status') }}</p>@endif
<a href="{{ route('marketplace') }}" class="text-sm underline">Back to cards</a>
<div class="grid md:grid-cols-2 gap-10 mt-6">
    <div class="space-y-4">@foreach($listing->images as $image)<img src="{{ Storage::disk('public')->url($image) }}" alt="{{ $listing->title }}" class="bg-white border rounded-xl w-full max-h-96 object-contain p-4">@endforeach</div>
    <div><p class="text-gray-500">{{ $listing->game }} · {{ $listing->condition }}</p><h1 class="text-3xl font-bold mt-3">{{ $listing->title }}</h1><p class="mt-2 text-gray-500">{{ $listing->set_name }}</p><p class="text-3xl font-bold text-amber-700 mt-6">RM {{ number_format((float)$listing->price, 2) }}</p><p class="mt-3">{{ $listing->quantity }} available</p>
    <a href="{{ route('marketplace.seller', $listing->seller) }}" class="block mt-6 underline">Sold by {{ $listing->seller->sellerApplication->shop_name }}</a>
    <h2 class="font-semibold mt-8">About this card</h2><p class="whitespace-pre-line mt-3">{{ $listing->description }}</p>
    <h2 class="font-semibold mt-8">Shipping · RM {{ number_format((float)$listing->shipping_price, 2) }}</h2><p class="whitespace-pre-line mt-3">{{ $listing->shipping_details }}</p>
    @if(auth()->id() !== $listing->seller_id)<a href="{{ route('orders.checkout', $listing) }}" class="inline-block mt-8 bg-gray-900 text-white px-5 py-3 rounded-lg">Create development order</a>@endif
    <p class="mt-4 p-4 bg-amber-50 rounded-lg">Local checkout only · no payment collected.</p></div>
</div>
@if($listing->tcgdex_id)<p class="mt-6">Pokémon printing: {{ $listing->tcgdex_id }} · Card #{{ $listing->card_number }} · {{ $listing->rarity }}</p>@endif
@auth
@if(auth()->id() !== $listing->seller_id)<details class="mt-8 border rounded-lg p-4"><summary class="cursor-pointer font-semibold">Report listing</summary><form method="POST" action="{{ route('listing.report', $listing) }}" class="space-y-3 mt-4">@csrf<x-validation-errors /><label for="report-reason">Explain what appears suspicious or incorrect</label><textarea id="report-reason" name="reason" required minlength="10" maxlength="2000" rows="4" class="w-full rounded-md border-gray-300">{{ old('reason') }}</textarea><x-button>Submit report</x-button></form></details>@endif
@else <a href="{{ route('login') }}" class="inline-block mt-6 underline">Log in to report this listing</a>@endauth
@endsection
