<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AddStock;
use App\Filament\Pages\EditStock;
use App\Filament\Pages\Stocks;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->brandName('Arcana Vault Admin')
            ->brandLogo(asset('arcana/logo.svg'))
            ->brandLogoHeight('16') // ✅ Logo height
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#52784d'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                Stocks::class,
                AddStock::class,
                EditStock::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigation(fn (NavigationBuilder $builder) => $builder
                ->items([
                    // ✅ Dashboard as first item
                    NavigationItem::make('Listing reports')->url(fn () => \App\Filament\Resources\ListingReportResource::getUrl()),
                    NavigationItem::make('Moderation history')->url(fn () => \App\Filament\Resources\ModerationLogResource::getUrl()),
                    NavigationItem::make('Seller applications')
                        ->icon('heroicon-o-users')
                        ->url(fn () => \App\Filament\Resources\SellerApplicationResource::getUrl()),
                    NavigationItem::make('Orders')
                        ->icon('heroicon-o-shopping-bag')
                        ->url(fn () => \App\Filament\Resources\OrderResource::getUrl()),
                    NavigationItem::make('Card listings')
                        ->icon('heroicon-o-rectangle-stack')
                        ->url(fn () => \App\Filament\Resources\ListingResource::getUrl()),
                    NavigationItem::make('Dashboard')
                        ->icon('heroicon-o-home')
                        ->url(fn () => route('filament.admin.pages.dashboard'))
                        ->isActiveWhen(fn () => request()->routeIs('filament.admin.pages.dashboard'))
                ])
                ->groups([
                    // ✅ Inventory as a group
                    NavigationGroup::make()
                        ->label('Inventory')
                        ->items([
                            NavigationItem::make('Stocks')
                                ->icon('heroicon-o-archive-box')
                                ->url(fn () => route('filament.admin.pages.stocks')),
                        ]),
                ])
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
