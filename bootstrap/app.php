<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Avoid guest middleware redirect loops on `/` by sending authenticated users
        // directly to their role-specific dashboard.
        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if (! $user) {
                return '/';
            }

            return $user->role?->dashboardRoute() ?? '/';
        });

        $middleware->alias([
            'role'             => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'       => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'profil.lengkap'   => \App\Http\Middleware\EnsureProfilLengkap::class,
            'akun.aktif'       => \App\Http\Middleware\EnsureAkunAktif::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureAkunAktif::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
