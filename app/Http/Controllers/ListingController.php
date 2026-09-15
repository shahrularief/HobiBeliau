<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    private function authorizeSeller(Request $request, ?Listing $listing = null): void
    {
        abort_unless($request->user()->isApprovedSeller(), 403, 'Seller approval is required.');
        if ($listing) { abort_unless($listing->seller_id === $request->user()->id, 403); }
    }
    public function index(Request $request)
    {
        $this->authorizeSeller($request);
        return view('seller.listings', ['listings' => Listing::where('seller_id', $request->user()->id)->latest()->paginate(15)]);
    }
    public function create(Request $request)
    {
        $this->authorizeSeller($request);
        return view('seller.listing-form', ['listing' => new Listing(['status' => 'published', 'quantity' => 1, 'shipping_price' => 0])]);
    }
    public function edit(Request $request, Listing $listing)
    {
        $this->authorizeSeller($request, $listing);
        return view('seller.listing-form', compact('listing'));
    }
    public function store(Request $request) { return $this->persist($request); }
    public function update(Request $request, Listing $listing) { return $this->persist($request, $listing); }
    private function persist(Request $request, ?Listing $listing = null)
    {
        $this->authorizeSeller($request, $listing);
        if (! $listing) {
            $request->merge(['status' => 'published']);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'game' => ['required', Rule::in(Listing::GAMES)],
            'set_name' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in(Listing::CONDITIONS)],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'shipping_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'shipping_details' => ['required', 'string', 'max:2000'],
            'status' => ['required', Rule::in(Listing::STATUSES)],
            'tcgdex_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9._-]{1,80}$/'],
            'photos' => [$listing ? 'nullable' : 'required', 'array', 'max:5'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        if ($data['status'] === 'published' && $data['quantity'] < 1) {
            throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'Published listings need at least one card.']);
        }
        $catalogId = $data['tcgdex_id'] ?? null;
        unset($data['tcgdex_id']);
        $catalogData = ['tcgdex_id' => null, 'card_number' => null, 'catalog_set_id' => null, 'rarity' => null];
        if ($catalogId) {
            try {
                $card = app(\App\Services\Tcgdex::class)->card($catalogId);
                if (($card['id'] ?? null) !== $catalogId || $data['game'] !== 'Pokémon' || $data['title'] !== $card['name'] || ($data['set_name'] ?? '') !== ($card['set']['name'] ?? '')) {
                    throw new \RuntimeException('Catalog selection does not match the listing.');
                }
                $catalogData = ['tcgdex_id' => $card['id'], 'card_number' => $card['localId'] ?? null, 'catalog_set_id' => $card['set']['id'] ?? null, 'rarity' => $card['rarity'] ?? null];
            } catch (\Throwable $exception) {
                throw \Illuminate\Validation\ValidationException::withMessages(['tcgdex_id' => 'Could not verify the selected card. Select it again or use manual entry.']);
            }
        }
        $oldImages = $listing?->images ?? [];
        $paths = [];
        try {
            foreach ($request->file('photos', []) as $photo) { $paths[] = $photo->store('listings', 'public'); }
            unset($data['photos']);
            $data['images'] = $paths ?: $oldImages;
            $listing ??= new Listing;
            $listing->fill($data);
            $listing->forceFill($catalogData);
            $listing->seller_id = $request->user()->id;
            $listing->save();
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }
        if ($paths) { Storage::disk('public')->delete($oldImages); }
        return redirect()->route('seller.listings.index')->with('status', 'Listing saved.');
    }
}
