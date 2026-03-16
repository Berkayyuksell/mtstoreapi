<?php

use App\Http\Controllers\ZplPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// ZPL → PNG önizleme (Labelary API)
// auth middleware: Filament ile aynı web session paylaşılır
Route::get('/admin/zpl-preview/{template}', [ZplPreviewController::class, 'show'])
    ->middleware('auth')
    ->name('zpl.preview');
