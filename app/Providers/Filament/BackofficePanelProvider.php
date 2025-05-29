<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
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
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use App\Filament\Pages\Auth\Register;
use App\Filament\Widgets\AttendanceDashboardOverview;
use App\Filament\Widgets\UsersAttendedTodayTable;
use App\Filament\Widgets\AdvancedStatsOverviewWidget;
use App\Filament\Widgets\AdvancedAttendanceLineChart;
use App\Filament\Widgets\AdvancedTableWidget;
use Awcodes\FilamentGravatar\GravatarPlugin;
use Awcodes\FilamentGravatar\GravatarProvider;

class BackofficePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->navigationGroups([
                'Manajamen Presensi',
                'Master Data',
                'Manajamen User',
            ])
            ->id('backoffice')
            ->path('backoffice')
            ->registration(Register::class)
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->assets([
                \Filament\Support\Assets\Css::make('leaflet', 'https://unpkg.com/leaflet/dist/leaflet.css'),
                \Filament\Support\Assets\Css::make('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'),
                \Filament\Support\Assets\Js::make('leaflet', 'https://unpkg.com/leaflet/dist/leaflet.js'),
                \Filament\Support\Assets\Css::make('custom-maps', asset('css/custom-maps.css')),
                \Filament\Support\Assets\Js::make('custom-maps', asset('js/custom-maps.js')),
            ])
            ->defaultAvatarProvider(GravatarProvider::class)
            ->widgets([
                AdvancedStatsOverviewWidget::class,
                AdvancedAttendanceLineChart::class,
                AdvancedTableWidget::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
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
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                FilamentProgressbarPlugin::make()->color('#fbc03a'),
                GravatarPlugin::make()
                    ->size(200)
                    ->rating('pg'),
            ]);
    }
}
