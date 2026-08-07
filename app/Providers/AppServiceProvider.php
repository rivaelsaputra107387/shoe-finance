<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix "Specified key was too long" error on older MySQL versions
        Schema::defaultStringLength(191);

        \App\Models\JournalEntry::observe(\App\Observers\AuditObserver::class);
        \App\Models\Account::observe(\App\Observers\AuditObserver::class);
        \App\Models\FiscalPeriod::observe(\App\Observers\AuditObserver::class);
    }
}
