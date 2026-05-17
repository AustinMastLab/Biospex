<?php

namespace App\Providers\Filament;

use App\Filament\Helpers\NavigationConfig;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin/dashboard')
            ->login()
            ->colors([
                'primary' => [
                    50 => '254, 242, 242',   // Very light red
                    100 => '254, 226, 226',  // Light red
                    200 => '254, 202, 202',  // Lighter red
                    300 => '252, 165, 165',  // Light red
                    400 => '248, 113, 113',  // Medium-light red
                    500 => '239, 68, 68',    // Medium red
                    600 => '232, 63, 41',    // Primary red (#e83f29)
                    700 => '185, 28, 28',    // Dark red
                    800 => '153, 27, 27',    // Darker red
                    900 => '127, 29, 29',    // Very dark red
                    950 => '69, 10, 10',     // Darkest red
                ],
                'gray' => [
                    50 => '240, 242, 245',   // Darker light gray (main background)
                    100 => '228, 232, 237',  // Darker gray (widget backgrounds)
                    200 => '215, 220, 227',  // Medium-light gray
                    300 => '185, 194, 205',  // Medium gray
                    400 => '135, 149, 165',  // Medium-dark gray
                    500 => '90, 105, 125',   // Dark gray
                    600 => '65, 78, 95',     // Darker gray
                    700 => '45, 58, 75',     // Very dark gray
                    800 => '28, 38, 52',     // Almost black
                    900 => '15, 23, 35',     // Very dark
                    950 => '5, 10, 18',      // Darkest
                ],
            ])
            ->darkMode(false)
            ->navigationGroups(NavigationConfig::getNavigationGroups())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([

            ])
            ->navigationItems([
                NavigationItem::make('Website')
                    ->url('/admin/projects')
                    ->icon('heroicon-o-window')
                    ->sort(-3),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');  // Use your default guard;
    }
}
