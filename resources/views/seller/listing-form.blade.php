<x-app-layout><x-slot name="header"><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ $listing->exists ? 'Edit card listing' : 'Add a card' }}</h2></x-slot>
<div class="max-w-3xl mx-auto p-6"><form method="POST" enctype="multipart/form-data" action="{{ $listing->exists ? route('seller.listings.update', $listing) : route('seller.listings.store') }}" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-6 rounded-xl space-y-5">
@csrf @if($listing->exists) @method('PUT') @endif
<x-validation-errors />
<input type="hidden" name="tcgdex_id" value="{{ old('tcgdex_id', $listing->tcgdex_id) }}">
<p class="text-sm">Catalog printing: {{ old('tcgdex_id', $listing->tcgdex_id) ?: 'Manual entry / no card selected' }}. <button type="button" onclick="this.form.elements.tcgdex_id.value = ''; this.closest('p').firstChild.textContent = 'Catalog printing: Manual entry. ';" class="underline">Use manual entry</button></p>
<div x-data="cardCatalog" class="border rounded-lg p-4 space-y-3">
    <h3 class="font-semibold">Find a Pokémon card</h3>
    <p class="text-sm">Search the English TCGdex catalog to fill the card name, game and set. Choose the correct printing. You can also enter details manually.</p>
    <label for="catalog-query" class="block text-sm">Card name</label>
    <div class="flex gap-2"><input id="catalog-query" x-model="query" @keydown.enter.prevent="search(1)" placeholder="e.g. Pikachu" class="rounded-md border-gray-300 dark:bg-gray-900 w-full"><button type="button" @click="search(1)" :disabled="busy" class="px-4 py-2 bg-gray-900 text-white dark:bg-gray-600 rounded-md">Search</button></div>
    <p x-text="message" role="status" class="text-sm"></p>
    <div class="grid sm:grid-cols-2 gap-2"><template x-for="card in cards" :key="card.id"><button type="button" @click="select(card.id)" :disabled="busy" class="text-left border rounded-md p-3 hover:bg-gray-100 dark:hover:bg-gray-700"><span class="block font-semibold" x-text="card.name"></span><span class="block text-sm" x-text="card.id + ' · #' + card.localId"></span></button></template></div>
    <div x-show="cards.length" class="flex gap-4"><button type="button" @click="search(page - 1)" :disabled="busy || page === 1">Previous</button><span x-text="'Page ' + page"></span><button type="button" @click="search(page + 1)" :disabled="busy || cards.length &lt; 12">Next</button></div>
    <p class="text-xs">Card data from <a href="https://tcgdex.dev/" target="_blank" rel="noopener" class="underline">TCGdex</a>. Upload photos of your actual card below; prices and condition are set by you.</p>
</div>
@foreach(['title' => 'Card name', 'set_name' => 'Set', 'price' => 'Price (RM)', 'quantity' => 'Quantity', 'shipping_price' => 'Shipping price (RM)'] as $field => $label)
<div><x-label :for="$field" :value="$label" /><x-input :id="$field" :name="$field" :value="old($field, $listing->$field)" :type="in_array($field, ['price', 'quantity', 'shipping_price']) ? 'number' : 'text'" :step="$field === 'quantity' ? '1' : '0.01'" class="mt-1 w-full" /></div>
@endforeach
@foreach(['game' => \App\Models\Listing::GAMES, 'condition' => \App\Models\Listing::CONDITIONS, 'status' => \App\Models\Listing::STATUSES] as $field => $options)
@if($field !== 'status' || $listing->exists)
<div><x-label :for="$field" :value="ucfirst($field)" /><select id="{{ $field }}" name="{{ $field }}" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@foreach($options as $option)<option value="{{ $option }}" @selected(old($field, $listing->$field) === $option)>{{ ucfirst($option) }}</option>@endforeach</select></div>
@endif
@endforeach
@foreach(['description' => 'Card description', 'shipping_details' => 'Shipping destinations and delivery details'] as $field => $label)<div><x-label :for="$field" :value="$label" /><textarea id="{{ $field }}" name="{{ $field }}" rows="4" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">{{ old($field, $listing->$field) }}</textarea></div>@endforeach
<div><x-label for="photos" value="Card photos" /><input id="photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-2 w-full" @required(!$listing->exists)><p class="text-sm mt-2">Up to 5 photos, 4 MB each. New uploads replace all existing photos. Leave empty to keep them.</p>
@if($listing->images)<div class="flex flex-wrap gap-3 mt-3">@foreach($listing->images as $image)<img src="{{ Storage::disk('public')->url($image) }}" alt="Current card photo" class="w-24 h-32 object-contain">@endforeach</div>@endif</div>
@if($listing->exists)
<p class="text-sm">Only published listings with available quantity appear in the marketplace. Use Paused to hide a card or Sold when it is no longer available.</p>
@else
<p class="text-sm">Your card will appear in the marketplace when you save it. You can pause it or mark it sold from the edit page later.</p>
@endif
<div class="flex gap-4 items-center"><x-button>Save listing</x-button><a href="{{ route('seller.listings.index') }}" class="underline">Cancel</a></div>
</form></div></x-app-layout>
