<x-app-layout>
    <div class="vault-dashboard" x-data="{ tab: 'buying', game: 'all' }">
        <section class="vault-hero">
            <div><span class="vault-eyebrow">YOUR COLLECTOR HQ</span><h1>Good to see you,<br>{{ $user->name }}<span class="vault-spark">✦</span></h1><p>Your next great find is out there. Make a little room in your vault.</p><a class="vault-button" href="{{ route('marketplace') }}">Explore the marketplace <span>↗</span></a></div>
            <div class="vault-art" aria-hidden="true"><div class="vault-art-card back">AV</div><div class="vault-art-card front"><span>ARCANA / COLLECTOR'S CLUB</span><b>✦</b><em>THE NEXT<br>GREAT FIND.</em><small>YOUR COLLECTION STARTS HERE</small></div><span class="vault-orbit">✧</span></div>
        </section>
        <div class="vault-toolbar"><div><span class="vault-eyebrow">AT A GLANCE</span><h2>Your vault, in motion.</h2></div><div class="vault-tabs" role="group" aria-label="Dashboard view"><button type="button" @click="tab = 'buying'" :aria-pressed="tab === 'buying'" :class="{ 'active': tab === 'buying' }">Buying</button>@if($approved)<button type="button" @click="tab = 'selling'" :aria-pressed="tab === 'selling'" :class="{ 'active': tab === 'selling' }">Selling</button>@endif</div></div>
        @foreach(['buying', 'selling'] as $mode)
        @if($mode === 'buying' || $approved)
        <section x-show="tab === '{{ $mode }}'" @if($mode === 'selling') x-cloak @endif>
            <div class="vault-stats">
                @foreach($mode === 'buying' ? [['↗', $purchaseCount, 'Purchases made', route('orders.index')], ['◷', $incomingCount, 'Orders in progress', route('orders.index')], ['✦', $completedCount, 'Completed orders', route('orders.index')]] : [['▣', $liveCount, 'Live listings', route('seller.listings.index')], ['↗', $saleCount, 'Sales received', route('orders.sales')], ['◷', $actionCount, 'Orders to prepare', route('orders.sales')]] as [$icon, $count, $label, $url])
                <a class="vault-stat" href="{{ $url }}"><span class="vault-stat-icon">{{ $icon }}</span><strong>{{ $count }}</strong><span>{{ $label }} <b>↗</b></span></a>
                @endforeach
            </div>
            <div class="vault-workspace">
                <section class="vault-box"><div class="vault-section-heading"><h2>{{ $mode === 'buying' ? 'Your recent finds' : 'Your latest sales' }}</h2><a href="{{ route($mode === 'buying' ? 'orders.index' : 'orders.sales') }}">View all ↗</a></div>
                @forelse($mode === 'buying' ? $recentPurchases : $recentSales as $order)
                <a class="vault-order" href="{{ route('orders.show', $order) }}"><span class="vault-order-icon">▣</span><div><strong>{{ $order->items->first()?->title ?? 'Card order' }}</strong><small>#{{ $order->id }} · {{ $order->created_at->format('d M Y') }} · RM {{ number_format($order->total_cents / 100, 2) }}</small></div><span class="vault-badge">{{ ucfirst($order->status) }}</span><span>→</span></a>
                @empty
                <div class="vault-empty"><span>✧</span><h3>{{ $mode === 'buying' ? 'A fresh page in your collection.' : 'Your shop is ready for its first sale.' }}</h3><p>{{ $mode === 'buying' ? 'Explore a card, place a demo order, and follow its journey here.' : 'Publish a card and share your shop to start the demo journey.' }}</p><a href="{{ route($mode === 'buying' ? 'marketplace' : 'seller.listings.index') }}">{{ $mode === 'buying' ? 'Find your first card' : 'Manage your listings' }} →</a></div>
                @endforelse
                </section>
                <aside class="vault-box vault-next"><span class="vault-eyebrow">YOUR NEXT MOVE</span>
                @if($user->selling_suspended)<h2>Selling is on pause.</h2><p>You can still track purchases and manage existing sales.</p><a class="vault-button" href="{{ route('orders.sales') }}">Manage existing sales ↗</a>
                @elseif($user->isApprovedSeller())<h2>Give a card<br>a new home.</h2><p>Your seller access is approved. Add your next listing or give your shop a personal touch.</p><a class="vault-button" href="{{ route('seller.listings.create') }}">＋ List a card</a><a class="vault-text-link" href="{{ route('seller.profile') }}">Edit your shop profile →</a>
                @else<h2>Collector today.<br>Seller tomorrow?</h2><p>Open your own shop and share your collection with other card fans.</p><span class="vault-badge">Seller application: {{ ucfirst($user->sellerApplication?->status ?? 'not started') }}</span><a class="vault-button" href="{{ route('seller.apply') }}">{{ $user->sellerApplication ? 'Check application' : 'Start your seller journey' }} ↗</a>@endif
                </aside>
            </div>
        </section>
        @endif
        @endforeach
        @if($user->is_admin)<a href="{{ url('/admin/listing-reports') }}" class="vault-admin"><span>◎</span><div><strong>Admin control room</strong><small>{{ $reportCount }} open listing {{ Str::plural('report', $reportCount) }} · Review reports and oversee the marketplace</small></div><b>Open admin ↗</b></a>@endif
        <section class="vault-discover"><div class="vault-section-heading"><div><span class="vault-eyebrow">FRESH FROM THE MARKETPLACE</span><h2>A little inspiration for your vault.</h2></div><a href="{{ route('marketplace') }}">Browse all ↗</a></div><div class="vault-filters" role="group" aria-label="Filter card previews"><button type="button" @click="game = 'all'" :class="{ active: game === 'all' }" :aria-pressed="game === 'all'">All finds</button>@foreach($cards->pluck('game')->unique() as $game)<button type="button" @click="game = @js($game)" :class="{ active: game === @js($game) }" :aria-pressed="game === @js($game)">{{ $game }}</button>@endforeach</div>
        <div class="vault-cards">@forelse($cards as $card)<a class="vault-card" href="{{ route('marketplace.show', $card) }}" x-show="game === 'all' || game === @js($card->game)"><div class="vault-card-image">@if($card->images)<img src="{{ Storage::disk('public')->url($card->images[0]) }}" alt="{{ $card->title }}" loading="lazy">@else<span aria-hidden="true">✦</span>@endif<span class="vault-card-arrow">↗</span></div><div class="vault-card-info"><small>{{ $card->game }}</small><h3>{{ $card->title }}</h3><div><strong>RM {{ number_format((float) $card->price, 2) }}</strong><span>{{ $card->condition }}</span></div></div></a>@empty<div class="vault-box">The marketplace is waiting for its first card. Check back soon!</div>@endforelse</div></section>
        <p class="vault-footnote">✦ Built for the joy of collecting. Portfolio demo — all payments are simulated.</p>
    </div>
</x-app-layout>
