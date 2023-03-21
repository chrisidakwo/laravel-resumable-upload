<?php

namespace ChrisIdakwo\ResumableUpload\Providers;

use Illuminate\Support\ServiceProvider;

class ResumableJsProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/resumable-upload.php', 'resumable-upload'
        );
    }

    public function boot(){

        /* Publish the config */
        $this->publishes([
            __DIR__ . '/../config/resumable-upload.php' => config_path('resumable-upload.php'),
        ]);

        /* Register Routes */
        $this->loadRoutesFrom(__DIR__.'/../routes/upload.php');

        /* Add migrations */
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }

}
