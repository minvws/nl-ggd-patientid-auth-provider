<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (
            config('app.env') === 'production' ||
            config('app.env') === 'acceptance' ||
            config('app.env') === 'testing'
        ) {
            URL::forceScheme('https');
        }

        Request::macro('patientHash', function () {
            /** @var Request $this */
            return $this->session()->get('hash', '');
        });
    }
}
