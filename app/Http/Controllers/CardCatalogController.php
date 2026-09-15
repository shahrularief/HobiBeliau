<?php
namespace App\Http\Controllers;
use App\Services\Tcgdex;
use Illuminate\Http\Request;
class CardCatalogController extends Controller {
    public function search(Request $request, Tcgdex $catalog) {
        abort_unless($request->user()->isApprovedSeller(), 403);
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100'], 'page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        try { return response()->json($catalog->search($data['q'], (int) ($data['page'] ?? 1))); }
        catch (\Throwable $exception) { report($exception); return response()->json(['message' => 'Card search is unavailable. Try again or enter your card manually.'], 503); }
    }
    public function show(Request $request, string $id, Tcgdex $catalog) {
        abort_unless($request->user()->isApprovedSeller(), 403);
        abort_unless(preg_match('/^[A-Za-z0-9._-]{1,80}$/', $id), 404);
        try { $card = $catalog->card($id); return response()->json(['id' => $card['id'], 'name' => $card['name'], 'set' => $card['set']['name'] ?? '', 'number' => $card['localId'] ?? '']); }
        catch (\Throwable $exception) { report($exception); return response()->json(['message' => 'Card details are unavailable. Try again or enter your card manually.'], 503); }
    }
}
