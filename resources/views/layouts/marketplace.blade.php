<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Arcana Vault · Card marketplace</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="arcana-marketplace bg-gray-50 text-gray-900 antialiased">
    <div class="arcana-demo-note">PORTFOLIO DEMO · FICTIONAL MARKETPLACE · NO REAL PAYMENTS</div>
    <header class="bg-white border-b"><nav class="max-w-7xl mx-auto px-4 py-5 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('landing') }}" class="text-xl font-bold"><img src="{{ asset('arcana/logo.svg') }}" alt="Arcana Vault — home" class="h-12 w-auto"></a>
        <div class="flex flex-wrap gap-4 items-center text-sm"> <a href="{{ route('marketplace') }}">Browse cards</a>
            @auth <a href="{{ route('dashboard') }}">My account</a><a href="{{ route('seller.apply') }}">Sell cards</a>
                <a href="{{ route('orders.index') }}">My purchases</a>
                @if(auth()->user()->hasSellerApproval())<a href="{{ route('orders.sales') }}">My sales</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="underline">Sign out</button></form>
            @else <a href="{{ route('login') }}">Log in</a><a href="{{ route('register') }}">Register</a>@endauth
        </div>
    </nav></header>
    <main class="max-w-7xl mx-auto px-4 py-10">@yield('content')</main>
    <footer class="border-t py-6 text-center text-sm text-gray-500"><a href="{{ route('landing') }}">ARCANA VAULT</a> · Portfolio marketplace demo · No real payments</footer>
</body></html>
