<?php

namespace App\Providers\Filament;

use App\Filament\Client\Pages\Auth\Register as ClientRegister;
use App\Filament\Client\Resources\OrderResource;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('client')
            ->path('client')
            ->brandName('Client Portal')
            // Use the dedicated login and registration methods
            ->login()
            // Point to the custom registration page class
            ->registration(\App\Filament\Client\Pages\Auth\Register::class)
            //->registration(ClientRegister::class)
            ->passwordReset()
            ->colors([
                'primary' => Color::Amber,
            ])
            // Revised navigation definition
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder->group(
                    NavigationGroup::make('Client Menu')
                        ->items([
                            // Use the Resource's getUrl() method for more robust routing
                            NavigationItem::make('My Orders')
                                ->url(fn(): string => OrderResource::getUrl('index'))
                                ->icon('heroicon-o-shopping-cart')
                                ->isActiveWhen(fn(): bool => request()->routeIs(OrderResource::getRouteBaseName() . '.*')),
                        ]),
                );
            })
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\\Filament\\Client\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Consider removing FilamentInfoWidget if not needed for clients
                // Widgets\FilamentInfoWidget::class,
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
                // This closure for role checking is fine
                // function ($request, $next) {
                //     if (!auth()->user()?->hasRole('client')) {
                //         abort(403, 'Unauthorized access.');
                //     }

                //     return $next($request);
                // },
            ])
            // Tenancy and multi-guard configurations can be added here if needed
        ;
    }
}
