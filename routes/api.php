<?php

use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\PanelAbonoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Notificaciones de Mercado Pago (sin sesión ni CSRF)
Route::post('/webhooks/mercadopago', [MercadoPagoController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('mp.webhook');

// Aviso firmado del Panel de Gestión: cambió un abono (sin sesión ni CSRF; se valida el HMAC)
Route::post('/panel/abono', [PanelAbonoController::class, 'recibir'])
    ->middleware('throttle:120,1')
    ->name('panel.abono');
