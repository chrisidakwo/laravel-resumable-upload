<?php

use Illuminate\Support\Facades\Route;
use ChrisIdakwo\ResumableUpload\Http\Controllers\UploadController;

Route::
prefix('upload')
    ->group(
        static function () {
            Route::post('init', [UploadController::class, 'init'])->name('resumablejs.init');
            Route::post('', [UploadController::class, 'upload']);
            Route::post('complete', [UploadController::class, 'complete']);
        }
    );
