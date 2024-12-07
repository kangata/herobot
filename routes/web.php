<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\BillingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Bots
    Route::resource('bots', BotController::class);

    // Knowledges
    Route::resource('knowledges', KnowledgeController::class);

    // Integrations
    Route::resource('integrations', IntegrationController::class);
    Route::get('/integrations/{integration}/qr', [IntegrationController::class, 'getQR'])->name('integrations.qr');
    Route::post('/integrations/{integration}/disconnect', [IntegrationController::class, 'disconnect'])->name('integrations.disconnect');

    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports');

    Route::get('/logout', [LogoutController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/bots/{bot}/connect-integration', [BotController::class, 'connectIntegration'])->name('bots.connect-integration');
    Route::delete('/bots/{bot}/disconnect-integration', [BotController::class, 'disconnectIntegration'])->name('bots.disconnect-integration');
    Route::post('/bots/{bot}/connect-knowledge', [BotController::class, 'connectKnowledge'])->name('bots.connect-knowledge');
    Route::delete('/bots/{bot}/disconnect-knowledge', [BotController::class, 'disconnectKnowledge'])->name('bots.disconnect-knowledge');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/topup', [BillingController::class, 'topup'])->name('billing.topup');
    Route::get('/billing/topup/success', [BillingController::class, 'topupSuccess'])->name('billing.topup.success');
    Route::get('/billing/topup/failure', [BillingController::class, 'topupFailure'])->name('billing.topup.failure');
});
