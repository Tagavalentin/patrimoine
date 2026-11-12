<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Livewire\Assets;
use App\Livewire\Dashboard;
use App\Livewire\Inventory;
use App\Livewire\Maintenance;
use App\Livewire\Reports;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('frontend.auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/biens', Assets\Index::class)->name('assets.index');
    Route::get('/biens/{asset}', Assets\Show::class)->name('assets.show');

    Route::get('/maintenance', Maintenance\Index::class)->name('maintenance.index');
    Route::get('/maintenance/nouvelle', Maintenance\Create::class)->name('maintenance.create');
    Route::get('/maintenance/{id}', Maintenance\Show::class)->name('maintenance.show');

    Route::get('/inventaires', Inventory\Index::class)->name('inventory.index');
    Route::get('/inventaires/nouvelle', Inventory\Create::class)->name('inventory.create');
    Route::get('/inventaires/{id}', Inventory\Show::class)->name('inventory.show');

    Route::get('/rapports', Reports\Index::class)->name('reports.index');
    Route::get('/rapports/export/{type}', ExportController::class)->name('reports.export');
});
