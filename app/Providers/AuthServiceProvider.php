<?php

namespace App\Providers;

use App\Models\User; // Pastikan model User di-import
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Definisikan Gate untuk 'isAdmin'
        Gate::define('is-admin', function (User $user) {
            return $user->role === User::ROLE_ADMIN;
        });

        // Anda juga bisa membuat Gate untuk 'isStaff' jika diperlukan di tempat lain
        Gate::define('is-staff', function (User $user) {
            return $user->role === User::ROLE_STAFF;
        });

        // Gate untuk mengakses fitur manajemen user (hanya admin)
        Gate::define('manage-users', function (User $user) {
            return $user->role === User::ROLE_ADMIN;
        });

        // Gate untuk mengakses fitur informasi toko (hanya admin)
        Gate::define('manage-store-info', function (User $user) {
            return $user->role === User::ROLE_ADMIN;
        });
    }
}
