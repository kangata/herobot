<?php

use App\Http\Controllers\WhatsAppMessageController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\WhatsAppServerAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware([WhatsAppServerAuth::class])->group(function () {
    Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);
    Route::post('/whatsapp/incoming-message', [WhatsAppMessageController::class, 'handleIncomingMessage']);
});
