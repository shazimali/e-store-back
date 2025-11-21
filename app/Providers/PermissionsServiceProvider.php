<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Skip if running in console (composer install, artisan migrate, etc.)
        if ($this->app->runningInConsole()) {
            return;
        }

        // Only proceed if the roles table exists
        if (Schema::hasTable('roles')) {
            try {
                $roles = Role::with('permissions')->get();
            } catch (\Exception $e) {
                // Skip if DB is not ready
                return;
            }

            $permissionsArray = [];

            foreach ($roles as $role) {
                foreach ($role->permissions as $permission) {
                    $permissionsArray[$permission->key][] = $role->id;
                }
            }

            // Define gates for each permission
            foreach ($permissionsArray as $permissionKey => $roleIds) {
                Gate::define($permissionKey, function ($user) use ($roleIds) {
                    return count(array_intersect($user->roles->pluck('id')->toArray(), $roleIds)) > 0;
                });
            }
        }
    }
}
