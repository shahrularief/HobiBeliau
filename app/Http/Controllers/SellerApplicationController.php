<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellerApplicationController extends Controller
{
    public function show(Request $request)
    {
        return view('seller.apply', ['application' => $request->user()->sellerApplication]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
        ]);
        // The unique user_id also prevents duplicate submissions from concurrent requests.
        $request->user()->sellerApplication()->firstOrCreate(['user_id' => $request->user()->id], $data);
        return redirect()->route('seller.apply')->with('status', 'Your seller application has been submitted.');
    }
}
