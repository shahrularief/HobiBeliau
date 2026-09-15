<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
class Tcgdex {
    public function search(string $name, int $page = 1): array {
        return Cache::remember('tcgdex.search.'.hash('sha256', $name.'.'.$page), 3600, fn () =>
            Http::acceptJson()->connectTimeout(3)->timeout(10)->get('https://api.tcgdex.net/v2/en/cards', ['name' => $name, 'pagination:page' => $page, 'pagination:itemsPerPage' => 12])->throw()->json()
        );
    }
    public function card(string $id): array {
        return Cache::remember('tcgdex.card.'.$id, 86400, fn () =>
            Http::acceptJson()->connectTimeout(3)->timeout(10)->get('https://api.tcgdex.net/v2/en/cards/'.rawurlencode($id))->throw()->json()
        );
    }
}
