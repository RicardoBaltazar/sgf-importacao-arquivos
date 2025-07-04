<?php

namespace App\Providers;

use App\Models\FinancialStatistic;
use App\Observers\FinancialStatisticObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

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
        Event::listen(Login::class, function ($event) {
            if (!Auth::viaRemember()) {
                Config::set('session.expire_on_close', true);
                Session::put('_session_configured', true);
                Session::migrate(true);
            }
        });

        FinancialStatistic::observe(FinancialStatisticObserver::class);
    }
}
