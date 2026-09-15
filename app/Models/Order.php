<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class Order extends Model {
    protected $guarded = ['id'];
    public function items() { return $this->hasMany(OrderItem::class); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public static function cents(string $amount): int {
        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
    public static function place(User $buyer, Listing $listing, array $data): self {
        return DB::transaction(function () use ($buyer, $listing, $data) {
            // Lock the buyer to serialize duplicate checkout submissions.
            User::whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            $existing = self::where('checkout_token', $data['checkout_token'])->first();
            if ($existing) { abort_unless($existing->buyer_id === $buyer->id, 403); return $existing; }
            $card = Listing::whereKey($listing->id)->lockForUpdate()->firstOrFail();
            abort_if($card->seller_id === $buyer->id, 403, 'You cannot order your own card.');
            if ($card->admin_hidden || $card->status !== 'published' || !$card->seller->isApprovedSeller() || $card->quantity < $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'This card is no longer available in that quantity.']);
            }
            // Conditional decrement protects inventory even when row locks are unavailable.
            $reserved = Listing::whereKey($card->id)->where('quantity', '>=', $data['quantity'])->where('status', 'published')->decrement('quantity', $data['quantity']);
            if ($reserved !== 1) { throw ValidationException::withMessages(['quantity' => 'Stock changed. Please try again.']); }
            $unit = self::cents($card->price);
            $shipping = self::cents($card->shipping_price);
            $order = self::create([
                'buyer_id' => $buyer->id, 'seller_id' => $card->seller_id,
                'checkout_token' => $data['checkout_token'], 'recipient' => $data['recipient'],
                'phone' => $data['phone'], 'address' => $data['address'],
                'subtotal_cents' => $unit * $data['quantity'], 'shipping_cents' => $shipping,
                'total_cents' => $unit * $data['quantity'] + $shipping,
                'payment_status' => 'not_collected', 'status' => 'placed',
            ]);
            $order->items()->create(['listing_id' => $card->id, 'title' => $card->title, 'game' => $card->game, 'condition' => $card->condition, 'quantity' => $data['quantity'], 'unit_price_cents' => $unit]);
            return $order;
        }, 3);
    }
    public function simulatePayment(User $buyer, string $outcome): void {
        abort_unless($buyer->id === $this->buyer_id, 403);
        abort_unless(in_array($outcome, ['success', 'failure'], true), 422);
        DB::transaction(function () use ($outcome) {
            $order = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->status === 'placed' && in_array($order->payment_status, ['not_collected', 'simulated_failure'], true), 409);
            $updated = self::whereKey($order->id)->where('status', 'placed')->where('payment_status', $order->payment_status)->update(['payment_status' => $outcome === 'success' ? 'simulated_success' : 'simulated_failure']);
            abort_unless($updated === 1, 409);
            $this->refresh();
        }, 3);
    }
    public function transition(User $actor, string $next, ?string $tracking = null): void {
        DB::transaction(function () use ($actor, $next, $tracking) {
            $order = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $isSeller = $actor->id === $order->seller_id && $actor->hasSellerApproval();
            if ($next === 'cancelled') {
                abort_unless($actor->id === $order->buyer_id || $isSeller, 403);
                abort_unless(in_array($order->status, ['placed', 'processing']), 409);
            } elseif ($next === 'completed') {
                abort_unless($actor->id === $order->buyer_id, 403);
                abort_unless($order->status === 'shipped', 409);
            } else {
                abort_unless($isSeller, 403);
                abort_unless(($next === 'processing' && $order->status === 'placed') || ($next === 'shipped' && $order->status === 'processing'), 409);
                if ($next === 'shipped') { abort_unless(filled($tracking), 422, 'Tracking details are required.'); }
            }
            $updated = self::whereKey($order->id)->where('status', $order->status)->update(['status' => $next, 'tracking' => $next === 'shipped' ? $tracking : $order->tracking]);
            abort_unless($updated === 1, 409);
            if ($next === 'cancelled') {
                foreach ($order->items as $item) { Listing::whereKey($item->listing_id)->increment('quantity', $item->quantity); }
            }
            $this->refresh();
        }, 3);
    }
}
