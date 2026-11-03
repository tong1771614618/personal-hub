<?php

use App\Http\Controllers\CategoryTagController;
use App\Http\Controllers\DrinkController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 首页：已登录跳 Capture，未登录跳登录
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/capture');
    }
    return Inertia::render('Welcome');
});

// ============ 页面路由（Inertia 渲染） ============
Route::middleware(['auth'])->group(function () {
    // Breeze 默认的 dashboard 名字保留，但指向 Capture 页
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/capture', [PageController::class, 'dashboard'])->name('capture');
    Route::get('/board', [PageController::class, 'board'])->name('board');
    Route::get('/stream', [PageController::class, 'stream'])->name('stream');
    Route::get('/review', [PageController::class, 'review'])->name('review');
    Route::get('/drinks', [PageController::class, 'drinks'])->name('drinks');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');

    // Profile (Breeze 自带)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ============ API 路由 ============
Route::prefix('api')->middleware(['auth'])->group(function () {
    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    // Entries
    Route::get('/entries', [EntryController::class, 'index']);
    Route::post('/entries', [EntryController::class, 'store']);
    Route::put('/entries/{entry}', [EntryController::class, 'update']);
    Route::delete('/entries/{entry}', [EntryController::class, 'destroy']);

    // Category tags
    Route::get('/category-tags', [CategoryTagController::class, 'index']);
    Route::post('/category-tags', [CategoryTagController::class, 'store']);
    Route::delete('/category-tags/{categoryTag}', [CategoryTagController::class, 'destroy']);

    // Review
    Route::get('/review/weekly', [ReviewController::class, 'weekly']);

    // Drinks
    Route::get('/drinks', [DrinkController::class, 'index']);
    Route::get('/drinks/stats', [DrinkController::class, 'stats']);
    Route::post('/drinks', [DrinkController::class, 'store']);
    Route::delete('/drinks/{drink}', [DrinkController::class, 'destroy']);

    // Admin: 用户管理
    Route::middleware([\App\Http\Middleware\EnsureAdmin::class])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});

require __DIR__.'/auth.php';
