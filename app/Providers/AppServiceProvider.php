<?php

namespace App\Providers;

use App\Http\View\Composers\CajaActivaComposer;
use App\Listeners\ActualizarUltimoLogin;
use App\Listeners\RegistrarLogin;
use App\Listeners\RegistrarLoginFallido;
use App\Listeners\RegistrarLogout;
use App\Models\Aportante;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use App\Policies\AportantePolicy;
use App\Policies\CajaPolicy;
use App\Policies\CatalogoEgresoPolicy;
use App\Policies\CatalogoIngresoPolicy;
use App\Policies\CorteCajaPolicy;
use App\Policies\DepartamentoPolicy;
use App\Policies\EgresoPolicy;
use App\Policies\IngresoPolicy;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        Gate::policy(Departamento::class, DepartamentoPolicy::class);
        Gate::policy(Caja::class, CajaPolicy::class);
        Gate::policy(CatalogoIngreso::class, CatalogoIngresoPolicy::class);
        Gate::policy(CatalogoEgreso::class, CatalogoEgresoPolicy::class);
        Gate::policy(Aportante::class, AportantePolicy::class);
        Gate::policy(Ingreso::class, IngresoPolicy::class);
        Gate::policy(Egreso::class, EgresoPolicy::class);
        Gate::policy(CorteCaja::class, CorteCajaPolicy::class);

        // Registro de eventos de autenticación para auditoría (RN-16)
        Event::listen(Login::class, ActualizarUltimoLogin::class);
        Event::listen(Login::class, RegistrarLogin::class);
        Event::listen(Logout::class, RegistrarLogout::class);
        Event::listen(Failed::class, RegistrarLoginFallido::class);

        // Inyección de Caja Activa en vistas
        View::composer('*', CajaActivaComposer::class);
    }
}
