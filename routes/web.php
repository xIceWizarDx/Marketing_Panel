<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlatformConnectionController;
use App\Http\Controllers\MediaLibraryController;
use App\Http\Controllers\SocialPostController;
use App\Http\Controllers\CalendarController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Marketing Panel routes
    Route::get('connections', [PlatformConnectionController::class, 'index'])->name('connections.index');
    Route::post('connections/{account}/status', [PlatformConnectionController::class, 'updateStatus'])->name('connections.status');
    Route::get('media', [MediaLibraryController::class, 'index'])->name('media.index');
    Route::post('media/upload', [MediaLibraryController::class, 'upload'])->name('media.upload');
    Route::get('media/list', [MediaLibraryController::class, 'list'])->name('media.list');
    Route::post('media/bulk-delete', [MediaLibraryController::class, 'bulkDelete'])->name('media.bulk_delete');
    Route::post('media/bulk-tag', [MediaLibraryController::class, 'bulkTag'])->name('media.bulk_tag');
    Route::get('posts', [SocialPostController::class, 'index'])->name('posts.index');
    Route::get('posts/create', [SocialPostController::class, 'create'])->name('posts.create');
    Route::post('posts/drafts', [SocialPostController::class, 'storeDraft'])->name('posts.drafts.store');
    Route::put('posts/{post}/draft', [SocialPostController::class, 'updateDraft'])->name('posts.drafts.update');
    Route::post('posts/{post}/platforms', [SocialPostController::class, 'savePlatforms'])->name('posts.platforms.store');
    Route::post('posts/{post}/media', [SocialPostController::class, 'saveMedia'])->name('posts.media.store');
    Route::post('posts/{post}/schedule', [SocialPostController::class, 'schedule'])->name('posts.schedule');
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
