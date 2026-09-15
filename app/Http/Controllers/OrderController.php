<?php
namespace App\Http\Controllers;
use App\Models\Listing;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class OrderController extends Controller {
    public function checkout(Request $request, Listing $listing) {
        abort_unless(Listing::visible()->whereKey($listing->id)->exists(), 404);
        abort_if($request->user()->id === $listing->seller_id, 403);
        return view('orders.checkout', ['listing' => $listing, 'token' => (string) Str::uuid()]);
    }
    public function store(Request $request, Listing $listing) {
        $data = $request->validate(['checkout_token' => ['required', 'uuid'], 'quantity' => ['required', 'integer', 'min:1', 'max:100000'], 'recipient' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:40'], 'address' => ['required', 'string', 'max:2000']]);
        $order = Order::place($request->user(), $listing, $data);
        return redirect()->route('orders.show', $order);
    }
    public function index(Request $request) {
        return view('orders.index', ['sales' => false, 'orders' => Order::where('buyer_id', $request->user()->id)->with('items')->latest()->paginate(15)]);
    }
    public function sales(Request $request) {
        abort_unless($request->user()->hasSellerApproval(), 403);
        return view('orders.index', ['sales' => true, 'orders' => Order::where('seller_id', $request->user()->id)->with('items')->latest()->paginate(15)]);
    }
    public function show(Request $request, Order $order) {
        abort_unless(in_array($request->user()->id, [$order->buyer_id, $order->seller_id]), 403);
        return view('orders.show', compact('order'));
    }
    public function simulatePayment(Request $request, Order $order) {
        $data = $request->validate(['outcome' => ['required', Rule::in(['success', 'failure'])]]);
        $order->simulatePayment($request->user(), $data['outcome']);
        return redirect()->route('orders.show', $order)->with('status', $data['outcome'] === 'success' ? 'Demo payment succeeded. No money was charged.' : 'Demo payment failed. Retry the simulation or cancel to restore stock.');
    }
    public function update(Request $request, Order $order) {
        $data = $request->validate(['status' => ['required', Rule::in(['processing', 'shipped', 'completed', 'cancelled'])], 'tracking' => ['nullable', 'string', 'max:255']]);
        $order->transition($request->user(), $data['status'], $data['tracking'] ?? null);
        return redirect()->route('orders.show', $order)->with('status', 'Order updated.');
    }
}
