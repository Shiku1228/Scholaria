<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        $composer = function ($view): void {
            $user = auth()->user();
            if (!$user || !Schema::hasTable('notifications')) {
                $view->with('headerNotifications', collect());
                $view->with('headerUnreadNotificationCount', 0);
                return;
            }

            try {
                $notifications = $user->notifications()->latest()->limit(8)->get();
                $unreadCount = (int) $user->unreadNotifications()->count();
            } catch (\Throwable) {
                $notifications = collect();
                $unreadCount = 0;
            }

            $view->with('headerNotifications', $notifications);
            $view->with('headerUnreadNotificationCount', $unreadCount);
        };

        View::composer('layouts.teacher', $composer);
        View::composer('layouts.student', $composer);
        View::composer('layouts.dashboard', $composer);
    }
}
