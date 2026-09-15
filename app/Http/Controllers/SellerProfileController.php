<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class SellerProfileController extends Controller {
    public function edit(Request $request) {
        abort_unless($request->user()->hasSellerApproval(), 403);
        return view('seller.profile', ['profile' => $request->user()->sellerApplication]);
    }
    public function update(Request $request) {
        abort_unless($request->user()->isApprovedSeller(), 403);
        $data = $request->validate(['shop_name' => ['required','string','max:100'], 'public_bio' => ['nullable','string','max:2000'], 'location' => ['nullable','string','max:100'], 'shipping_policy' => ['nullable','string','max:2000']]);
        $request->user()->sellerApplication->forceFill($data)->save();
        return redirect()->route('seller.profile')->with('status', 'Shop profile saved.');
    }
}
