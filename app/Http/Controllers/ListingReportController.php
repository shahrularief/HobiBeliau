<?php
namespace App\Http\Controllers;
use App\Models\{Listing, ListingReport};
use Illuminate\Http\Request;
class ListingReportController extends Controller {
    public function store(Request $request, Listing $listing) {
        abort_unless(Listing::visible()->whereKey($listing->id)->exists(), 404);
        abort_if($listing->seller_id === $request->user()->id, 403);
        $data = $request->validate(['reason' => ['required','string','min:10','max:2000']]);
        ListingReport::firstOrCreate(['listing_id' => $listing->id, 'reporter_id' => $request->user()->id], ['reason' => $data['reason']]);
        return redirect()->route('marketplace.show', $listing)->with('report_status', 'Your report has been received. An administrator will review it.');
    }
}
