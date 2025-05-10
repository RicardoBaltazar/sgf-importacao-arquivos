<?php

namespace App\Providers;

use Filament\Pages\Auth\EditProfile;
use Filament\Pages\Auth\Login;
use Filament\Pages\Auth\Register;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;

class FilamentAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar as páginas de autenticação do Filament
        Panel::configureUsing(function (Panel $panel): void {
            $panel
                ->login(Login::class)
                ->registration(Register::class)
                ->profile(EditProfile::class);
        });
    }
}
