<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
@forelse($listings as $card)
    <a href="{{ route('marketplace.show', $card) }}" class="bg-white border rounded-xl overflow-hidden hover:shadow-lg transition">
        <div class="h-64 bg-gray-100 flex items-center justify-center">@if($card->images)<img src="{{ Storage::disk('public')->url($card->images[0]) }}" alt="{{ $card->title }}" class="w-full h-full object-contain p-4" loading="lazy">@endif</div>
        <div class="p-5"><p class="text-xs text-gray-500">{{ $card->game }} · {{ $card->condition }}</p><h2 class="font-semibold mt-2">{{ $card->title }}</h2><p class="text-sm text-gray-500 mt-1">{{ $card->set_name }}</p><p class="text-amber-700 font-bold text-lg mt-4">RM {{ number_format((float)$card->price, 2) }}</p><p class="text-sm text-gray-600 mt-2">Sold by {{ $card->seller->sellerApplication->shop_name }}</p><p class="text-xs text-gray-500 mt-1">{{ $card->quantity }} available</p></div>
    </a>
@empty
    <div class="col-span-full border border-dashed rounded-xl p-12 text-center bg-white"><h2 class="text-xl font-semibold">No cards listed yet</h2><p class="mt-3 text-gray-500">Try different filters, or apply to become one of our first sellers.</p></div>
@endforelse
</div><div class="mt-8">{{ $listings->links() }}</div>
