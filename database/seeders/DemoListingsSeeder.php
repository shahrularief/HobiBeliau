<?php
namespace Database\Seeders;
use App\Models\{Listing, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
class DemoListingsSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::where('email', 'shahrul.arief.sa@gmail.com')->where('selling_suspended', false)
            ->whereHas('sellerApplication', fn ($query) => $query->where('status', 'approved'))->firstOrFail();
        $cards = [
            ['Pikachu', 'Pokémon', 15], ['Charizard', 'Pokémon', 120], ['Gengar', 'Pokémon', 45],
            ['Eevee', 'Pokémon', 12], ['Monkey D. Luffy', 'One Piece', 35], ['Roronoa Zoro', 'One Piece', 28],
            ['Nami', 'One Piece', 18], ['Dark Magician', 'Yu-Gi-Oh!', 40],
            ['Blue-Eyes White Dragon', 'Yu-Gi-Oh!', 55], ['Lightning Bolt', 'Magic: The Gathering', 10],
        ];
        foreach ($cards as $index => [$name, $game, $price]) {
            $path = 'listings/demo-'.($index + 1).'.svg';
            $label = htmlspecialchars($name, ENT_QUOTES | ENT_XML1, 'UTF-8');
            Storage::disk('public')->put($path, '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="560" viewBox="0 0 400 560"><rect width="400" height="560" rx="24" fill="#111827"/><rect x="20" y="20" width="360" height="520" rx="18" fill="none" stroke="#f59e0b" stroke-width="3"/><text x="200" y="100" text-anchor="middle" fill="#f59e0b" font-family="sans-serif" font-size="20">Arcana Vault DEMO</text><text x="200" y="280" text-anchor="middle" fill="white" font-family="sans-serif" font-size="18">'.$label.'</text><text x="200" y="470" text-anchor="middle" fill="#d1d5db" font-family="sans-serif" font-size="16">Placeholder · no real card</text></svg>');
            Listing::updateOrCreate(['seller_id' => $seller->id, 'title' => '[DEMO] '.$name], [
                'game' => $game, 'set_name' => 'Demo catalog — printing unspecified',
                'condition' => Listing::CONDITIONS[$index % count(Listing::CONDITIONS)],
                'description' => 'DEMO LISTING for testing browsing and local orders. No physical card is offered. Image, price, condition and stock are sample data; do not pay or ship anything.',
                'price' => $price, 'quantity' => 3 + $index, 'shipping_price' => 5,
                'shipping_details' => 'Demo delivery within Malaysia. No actual shipment.',
                'images' => [$path], 'status' => 'published',
            ]);
        }
    }
}
