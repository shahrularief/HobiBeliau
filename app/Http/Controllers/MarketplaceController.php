<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'game' => ['nullable', Rule::in(Listing::GAMES)], 'condition' => ['nullable', Rule::in(Listing::CONDITIONS)],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc'])],
        ]);
        if (isset($filters['min_price'], $filters['max_price']) && $filters['min_price'] > $filters['max_price']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['max_price' => 'Maximum price must be at least the minimum price.']);
        }
        $listings = Listing::visible()->with('seller.sellerApplication')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$search.'%')->orWhere('set_name', 'like', '%'.$search.'%')))
            ->when($filters['game'] ?? null, fn ($query, $game) => $query->where('game', $game))
            ->when($filters['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('price', '<=', $filters['max_price']));
        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $listings->orderBy('price'),
            'price_desc' => $listings->orderByDesc('price'),
            default => $listings->latest(),
        };
        $listings = $listings->orderByDesc('id')->paginate(12)->withQueryString();
        return view('marketplace.index', compact('listings'));
    }
    public function show(Listing $listing)
    {
        $listing = Listing::visible()->with('seller.sellerApplication')->findOrFail($listing->id);
        return view('marketplace.show', compact('listing'));
    }
    public function seller(User $seller)
    {
        abort_unless($seller->isApprovedSeller(), 404);
        return view('marketplace.seller', ['seller' => $seller, 'listings' => Listing::visible()->with('seller.sellerApplication')->where('seller_id', $seller->id)->latest()->paginate(12)]);
    }
}
