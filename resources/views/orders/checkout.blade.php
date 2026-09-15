@extends('layouts.marketplace')
@section('content')
<div class="max-w-2xl mx-auto"><h1 class="text-3xl font-bold">Local checkout</h1><p class="mt-4 bg-amber-50 p-4 rounded-lg">This creates a development order and reserves stock. No payment is collected. Do not send money or ship real cards for these orders.</p>
<h2 class="text-xl font-semibold mt-6">{{ $listing->title }}</h2><p class="mt-2">RM {{ $listing->price }} per card + RM {{ $listing->shipping_price }} shipping per order.</p><p class="mt-2 whitespace-pre-line">{{ $listing->shipping_details }}</p>
<p class="mt-4 text-sm">After creating your order, try a simulated payment success or failure. No money is collected.</p><form method="POST" action="{{ route('orders.store', $listing) }}" class="space-y-4 mt-6">@csrf<input type="hidden" name="checkout_token" value="{{ old('checkout_token', $token) }}"><x-validation-errors />
<div><x-label for="quantity" value="Quantity" /><x-input id="quantity" name="quantity" type="number" min="1" :max="$listing->quantity" :value="old('quantity', 1)" required class="w-full" /></div>
@foreach(['recipient' => 'Recipient name', 'phone' => 'Contact phone', 'address' => 'Shipping address including postcode and country'] as $field => $label)<div><x-label :for="$field" :value="$label" /><x-input :id="$field" :name="$field" :value="old($field)" required class="w-full" /></div>@endforeach
<x-button>Create development order · no payment</x-button></form></div>
<script>
    (() => {
        const quantity = document.getElementById('quantity');
        const summary = document.createElement('div');
        summary.className = 'bg-white border rounded-lg p-4';
        summary.setAttribute('aria-live', 'polite');
        quantity.form.querySelector('button[type="submit"]').before(summary);
        const unit = {{ \App\Models\Order::cents($listing->price) }};
        const shipping = {{ \App\Models\Order::cents($listing->shipping_price) }};
        const money = cents => 'RM ' + (cents / 100).toFixed(2);
        const update = () => {
            const count = Number(quantity.value);
            if (!Number.isInteger(count) || count < 1 || count > Number(quantity.max)) {
                summary.textContent = 'Enter a valid available quantity to see your total.';
                return;
            }
            summary.textContent = 'Subtotal: ' + money(unit * count) + ' · Shipping: ' + money(shipping) + ' · Total: ' + money(unit * count + shipping) + ' · No payment collected';
        };
        quantity.addEventListener('input', update);
        update();
    })();
</script>
@endsection
