<?php

use Illuminate\Support\Facades\Route;
use ChrisIdakwo\ResumableUpload\Http\Controllers\UploadController;

Route::prefix(config('resumablejs.route.prefix'))
    ->as(config('resumablejs.route.as'))
    ->group(
        static function () {
            Route::post('init', [UploadController::class, 'init'])->name('init');
            Route::post('', [UploadController::class, 'upload']);
            Route::post('complete', [UploadController::class, 'complete']);
        }
    );
