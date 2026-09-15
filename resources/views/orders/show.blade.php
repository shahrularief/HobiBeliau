@extends('layouts.marketplace')
@section('content')
<div class="max-w-3xl mx-auto"><h1 class="text-3xl font-bold">Order #{{ $order->id }}</h1><p class="p-4 bg-amber-50 mt-4 rounded-lg">Development order · no payment collected. Fulfilment controls simulate the workflow; do not ship real cards.</p><p class="mt-4 font-semibold">Status: {{ ucfirst($order->status) }}</p>
@if(session('status'))<p class="mt-4">{{ session('status') }}</p>@endif<x-validation-errors class="mt-4" />
<section class="arcana-panel mt-6"><h2 class="font-semibold text-xl">Payment simulation</h2>
<p class="mt-3">{{ match($order->payment_status) { 'simulated_success' => 'Demo payment succeeded — no money charged.', 'simulated_failure' => 'Demo payment failed — no money charged.', default => 'No demo payment attempted.' } }}</p>
@if(auth()->id() === $order->buyer_id && $order->status === 'placed' && $order->payment_status !== 'simulated_success')
<p class="mt-3 text-sm">Choose an outcome. Failure keeps stock reserved for retry; cancellation restores it. No payment credentials are needed.</p>
<form method="POST" action="{{ route('orders.demo-payment', $order) }}" class="mt-5 flex flex-wrap gap-3">@csrf<button type="submit" name="outcome" value="success" class="px-4 py-3">Simulate success</button><button type="submit" name="outcome" value="failure" class="px-4 py-3">Simulate failure</button></form>
@endif
@if($order->payment_status === 'simulated_success')<p class="arcana-payment-success p-4 mt-4 rounded-lg">Success: demo checkout complete. No payment was taken.</p>@elseif($order->payment_status === 'simulated_failure')<p class="arcana-payment-failed p-4 mt-4 rounded-lg">Failure: retry while the order is placed, or cancel before shipping.</p>@endif
</section>
@foreach($order->items as $item)<div class="bg-white border rounded-xl p-5 mt-5"><h2 class="font-semibold">{{ $item->title }}</h2><p>{{ $item->game }} · {{ $item->condition }}</p><p>{{ $item->quantity }} × RM {{ number_format($item->unit_price_cents / 100, 2) }}</p></div>@endforeach
<p class="mt-5">Subtotal: RM {{ number_format($order->subtotal_cents / 100, 2) }}</p><p>Shipping: RM {{ number_format($order->shipping_cents / 100, 2) }}</p><p class="font-bold mt-2">Total: RM {{ number_format($order->total_cents / 100, 2) }}</p>
<h2 class="font-semibold mt-6">Shipping details</h2><p>{{ $order->recipient }} · {{ $order->phone }}</p><p class="whitespace-pre-line">{{ $order->address }}</p>@if($order->tracking)<p class="mt-4">Tracking: {{ $order->tracking }}</p>@endif
<div class="space-y-4 mt-6">
@if(auth()->id() === $order->seller_id && auth()->user()->hasSellerApproval() && in_array($order->status, ['placed', 'processing']))
<form method="POST" action="{{ route('orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $order->status === 'placed' ? 'processing' : 'shipped' }}">@if($order->status === 'processing')<x-label for="tracking" value="Carrier and tracking number" /><x-input id="tracking" name="tracking" required maxlength="255" class="mb-3 w-full" />@endif<x-button>{{ $order->status === 'placed' ? 'Start processing' : 'Mark shipped' }}</x-button></form>
@endif
@if(auth()->id() === $order->buyer_id && $order->status === 'shipped')<form method="POST" action="{{ route('orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><x-button>Confirm received</x-button></form>@endif
@if(in_array($order->status, ['placed', 'processing']) && (auth()->id() === $order->buyer_id || auth()->user()->hasSellerApproval()))<form method="POST" action="{{ route('orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><x-button>Cancel order and restore stock</x-button></form>@endif
</div></div>
@endsection
