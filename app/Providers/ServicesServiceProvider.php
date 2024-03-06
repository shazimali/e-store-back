<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Auth\AuthClass;
use App\Services\Auth\AuthInterface;
use App\Services\Users\UsersClass;
use App\Services\Users\UsersInterface;
use App\Services\Roles\RolesClass;
use App\Services\Roles\RolesInterface;
use App\Services\Categories\CategoriesClass;
use App\Services\Categories\CategoriesInterface;

class ServicesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AuthInterface::class, AuthClass::class);
        $this->app->bind(UsersInterface::class, UsersClass::class);
        $this->app->bind(RolesInterface::class, RolesClass::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
