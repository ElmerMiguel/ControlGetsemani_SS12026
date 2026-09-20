<?php

namespace App\Providers;

use App\Listeners\ActualizarUltimoLogin;
use App\Listeners\RegistrarLogin;
use App\Listeners\RegistrarLoginFallido;
use App\Listeners\RegistrarLogout;
use App\Models\User;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Prohíbe comandos destructivos de base de datos (como migrate:fresh o db:wipe) en producción.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Activa validaciones estrictas en Eloquent fuera de producción contra lazy loading y atributos no asignables.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Establece el idioma español para la manipulación y formateo de fechas con la librería Carbon.
        Carbon::setLocale('es');

        // Registro de políticas de autorización
        Gate::policy(User::class, UserPolicy::class);

        // Registro de eventos de autenticación para auditoría (RN-16)
        Event::listen(Login::class, ActualizarUltimoLogin::class);
        Event::listen(Login::class, RegistrarLogin::class);
        Event::listen(Logout::class, RegistrarLogout::class);
        Event::listen(Failed::class, RegistrarLoginFallido::class);
    }
}
