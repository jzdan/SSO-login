<?php

namespace App\Providers;

use App\Models\Passport\Client;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Passport::useClientModel(Client::class);
        Passport::authorizationView('passport.authorize');

        Passport::tokensCan([
            'profile' => 'Melihat nama dan informasi profil Anda',
            'email' => 'Melihat alamat email Anda',
        ]);
        Passport::defaultScopes(['profile', 'email']);

        Passport::tokensExpireIn(now()->addMinutes((int) config('sso.token_ttl')));
        Passport::refreshTokensExpireIn(now()->addDays((int) config('sso.refresh_token_ttl_days')));
    }
}
