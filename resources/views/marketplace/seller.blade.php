@extends('layouts.marketplace')
@section('content')
<p class="text-sm text-amber-700">Approved seller · Joined {{ $seller->created_at->format('M Y') }}</p><h1 class="text-3xl font-bold mt-3">{{ $seller->sellerApplication->shop_name }}</h1><p class="mt-4 whitespace-pre-line">{{ $seller->sellerApplication->public_bio ?: 'Welcome to this seller’s card shop.' }}</p><p class="mt-3">{{ $seller->sellerApplication->location }}</p>
@if($seller->sellerApplication->shipping_policy)<h2 class="font-semibold mt-6">Shipping policy</h2><p class="mt-2 whitespace-pre-line">{{ $seller->sellerApplication->shipping_policy }}</p>@endif
@include('marketplace.grid')
@endsection
