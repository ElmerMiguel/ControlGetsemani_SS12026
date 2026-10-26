# Sistema de Finanzas e Inventario — Iglesia Getsemaní

Sistema web integral para el control de ingresos, egresos, saldos en tiempo real, inventario institucional, comprobantes de donación en PDF y consolidación de informes de asamblea. Desarrollado con Laravel 12, PHP 8.2, MariaDB y Docker.

---

## Despliegue Continuo (CI/CD)

El proyecto cuenta con un flujo automatizado de entrega y despliegue continuo mediante **GitHub Actions** (`.github/workflows/deploy.yml`), el cual se activa exclusivamente ante eventos `push` sobre la rama `main` (tras la revisión y fusión de un Pull Request):

1. **Job `test` (Calidad y Verificación):** Ejecuta el linter estricto de Laravel (`Pint`), compila los activos de frontend con Vite, aplica migraciones sobre una base de datos MySQL 8.0 aislada y ejecuta la suite completa de 166 pruebas automatizadas (PHPUnit). Si ocurre cualquier falla, el pipeline se aborta inmediatamente.
2. **Job `build` (Construcción Docker):** Valida la construcción de la imagen de producción multi-stage (`docker/app/Dockerfile`) garantizando que no se incluyan dependencias de desarrollo ni `node_modules`.
3. **Job `deploy` (Despliegue y Migraciones):** Se conecta vía SSH seguro al servidor VPS/instancia de producción, actualiza el repositorio (`git reset --hard origin/main`), reconstruye los contenedores en segundo plano (`docker compose -f docker-compose.prod.yml up -d --build`), corre las migraciones con `--force` y optimiza las cachés de configuración, rutas y vistas.
4. **Verificación de Salud (`Healthcheck`):** Realiza un sondeo automático contra `https://<dominio>/up` esperando un código de respuesta HTTP `200 OK`. Si el servidor no responde saludablemente, el job finaliza en estado fallido para alertar al equipo.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
