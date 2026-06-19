<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SketchController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects (CRUD + Kanban)
    Route::resource('projects', ProjectController::class)->except(['create', 'edit']);

    // Tasks
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/update-positions', [TaskController::class, 'updatePositions'])->name('tasks.updatePositions');

    // Sketches / Whiteboard
    Route::resource('sketches', SketchController::class)->except(['create', 'edit', 'update']);
    Route::post('/sketches/{sketch}/save-canvas', [SketchController::class, 'saveCanvas'])->name('sketches.saveCanvas');

    // Finance
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance', [FinanceController::class, 'store'])->name('finance.store');
    Route::put('/finance/{transaction}', [FinanceController::class, 'update'])->name('finance.update');
    Route::delete('/finance/{transaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
