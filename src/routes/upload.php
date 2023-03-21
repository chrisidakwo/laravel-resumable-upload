<?php

use Illuminate\Support\Facades\Route;
use ChrisIdakwo\ResumableUpload\Http\Controllers\UploadController;

Route::prefix(config('resumable-upload.route.prefix'))
    ->as(config('resumable-upload.route.as'))
    ->group(
        static function () {
            Route::post('init', [UploadController::class, 'init'])->name('init');
            Route::post('', [UploadController::class, 'upload'])->name('index');
            Route::post('complete', [UploadController::class, 'complete'])->name('complete');
        }
    );
