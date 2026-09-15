@extends('layouts.marketplace')
@section('content')
<h1 class="text-3xl font-bold">{{ $sales ? 'My sales' : 'My purchases' }}</h1><p class="mt-3 text-gray-600">Development orders · no payments collected</p>
@forelse($orders as $order)<a href="{{ route('orders.show', $order) }}" class="block bg-white border rounded-xl p-5 mt-5"><h2 class="font-semibold">Order #{{ $order->id }} · {{ $order->items->first()?->title }}</h2><p class="mt-2">{{ ucfirst($order->status) }} · RM {{ number_format($order->total_cents / 100, 2) }} · {{ $order->created_at->format('d M Y') }}</p></a>@empty<p class="mt-8">No orders yet.</p>@endforelse
<div class="mt-6">{{ $orders->links() }}</div>
@endsection
