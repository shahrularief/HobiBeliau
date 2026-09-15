<?php
namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use App\Models\ListingReport;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $approved = $user->hasSellerApproval();
        $purchases = Order::where('buyer_id', $user->id);
        $sales = Order::where('seller_id', $user->id);
        return view('dashboard', [
            'user' => $user,
            'approved' => $approved,
            'purchaseCount' => (clone $purchases)->count(),
            'incomingCount' => (clone $purchases)->whereIn('status', ['placed', 'processing', 'shipped'])->count(),
            'completedCount' => (clone $purchases)->where('status', 'completed')->count(),
            'recentPurchases' => (clone $purchases)->with('items')->latest()->limit(4)->get(),
            'saleCount' => $approved ? (clone $sales)->count() : 0,
            'actionCount' => $approved ? (clone $sales)->whereIn('status', ['placed', 'processing'])->count() : 0,
            'recentSales' => $approved ? (clone $sales)->with('items')->latest()->limit(4)->get() : collect(),
            'liveCount' => Listing::visible()->where('seller_id', $user->id)->count(),
            'cards' => Listing::visible()->with('seller.sellerApplication')->latest()->limit(8)->get(),
            'reportCount' => $user->is_admin ? ListingReport::where('status', 'open')->count() : 0,
        ]);
    }
}
