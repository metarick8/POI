<?php

namespace App\Providers;

use App\Models\Debate;
use App\Policies\ApplicationPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Debate::class, ApplicationPolicy::class);
    }
}
