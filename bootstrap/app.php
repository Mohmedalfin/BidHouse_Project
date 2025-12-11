<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// --- PERHATIKAN BARIS INI (JANGAN SALAH PILIH) ---
use Illuminate\Console\Scheduling\Schedule; 
// -------------------------------------------------

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Pastikan nama command sesuai dengan signature di file Command Anda
        $schedule->command('lelang:tutup-otomatis')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();