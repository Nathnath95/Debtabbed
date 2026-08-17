<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('households', HouseholdController::class);
    Route::get('/households/join/{code}', [HouseholdController::class, 'showJoin'])->name('households.showJoin');
    Route::post('/households/join/{code}', [HouseholdController::class, 'join'])->name('households.join');
    Route::resource('households.expenses', ExpenseController::class)->shallow();
    Route::patch('/expenses/{expense}/splits', [ExpenseController::class, 'updateSplits'])->name('expenses.splits.update');
});



require __DIR__.'/auth.php';
