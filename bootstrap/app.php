<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Di VPS (CloudPanel) request lewat proxy lokal: Nginx -> (Varnish) -> Nginx :8080 -> PHP,
        // sehingga PHP melihat semua pengunjung sebagai 127.0.0.1. Header X-Forwarded-* hanya
        // dipercaya dari proxy lokal itu, supaya request()->ip() = IP asli pengunjung
        // (dipakai batas percobaan login). Header palsu dari luar tetap diabaikan.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
