<?php

use App\Http\Controllers\MercadoPagoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Notificaciones de Mercado Pago (sin sesión ni CSRF)
Route::post('/webhooks/mercadopago', [MercadoPagoController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('mp.webhook');
