<?php

namespace App\Providers;

use App\Http\Controllers\CatFaqController;
use App\Http\Controllers\FaqController;
use App\Repository\CatFaqRepository;
use App\Repository\FaqRepository;
use App\Repository\RepositoryInterface;
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

 $this->app->when(FaqController::class)
            ->needs(RepositoryInterface::class)
            ->give(function () {
                return new FaqRepository;
            });
        $this->app->when(CatFaqController::class)
            ->needs(RepositoryInterface::class)
            ->give(function () {
                return new CatFaqRepository;
            });    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

