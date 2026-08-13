<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        JWTAuth::setTokenTTL(60);
    }

    public function boot(): void
    {
        $this->app->bind(JWTSubject::class, User::class);
    }
}
