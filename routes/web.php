<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\KnowledgeController;
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

    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports');

    Route::get('/logout', [LogoutController::class, 'logout']);
});
