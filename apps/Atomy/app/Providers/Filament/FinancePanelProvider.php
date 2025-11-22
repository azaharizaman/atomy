<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;

/**
 * Finance Panel Provider
 * 
 * Dedicated panel for Finance domain resources (Accounts, Journal Entries, etc.)
 * Isolated from other panels for better organization and security.
 */
final class FinancePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('finance')
            ->path('finance')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
                'gray' => Color::Zinc,
            ])
            ->brandName('Nexus Finance')
            ->brandLogo('/images/finance-logo.svg')
            ->brandLogoHeight('2rem')
            ->favicon('/images/favicon.ico')
            ->discoverResources(in: app_path('Filament/Finance/Resources'), for: 'App\\Filament\\Finance\\Resources')
            ->discoverPages(in: app_path('Filament/Finance/Pages'), for: 'App\\Filament\\Finance\\Pages')
            ->discoverWidgets(in: app_path('Filament/Finance/Widgets'), for: 'App\\Filament\\Finance\\Widgets')
            ->pages([])
            ->widgets([])
            ->middleware([
                \App\Http\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                \Illuminate\Session\Middleware\AuthenticateSession::class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
                \App\Http\Middleware\CheckAdminRole::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('General Ledger')
                    ->label('General Ledger')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(false),
                NavigationGroup::make('Reporting')
                    ->label('Reporting')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(true),
                NavigationGroup::make('Configuration')
                    ->label('Configuration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->spa();
    }
}
