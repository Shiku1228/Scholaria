<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Observers\TransactionObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
        // Register transaction observer
        Transaction::observe(TransactionObserver::class);

        RateLimiter::for('chat-messages', function (Request $request) {
            $userId = (int) optional($request->user())->id;
            return Limit::perMinute(45)->by($userId . '|' . $request->ip());
        });

        $composer = function ($view): void {
            $user = auth()->user();
            if (!$user) {
                $view->with('headerNotifications', collect());
                $view->with('headerUnreadNotificationCount', 0);
                $view->with('headerUnreadMessageCount', 0);
                return;
            }

            $notifications = collect();
            $unreadCount = 0;
            try {
                if (Schema::hasTable('notifications')) {
                    $notifications = $user->notifications()->latest()->limit(8)->get();
                    $unreadCount = (int) $user->unreadNotifications()->count();
                }
            } catch (\Throwable) {
                $notifications = collect();
                $unreadCount = 0;
            }

            $unreadMessages = 0;
            try {
                if (Schema::hasTable('chat_conversation_user') && Schema::hasTable('chat_messages')) {
                    $unreadMessages = (int) DB::table('chat_messages as m')
                        ->join('chat_conversation_user as cu', 'cu.chat_conversation_id', '=', 'm.chat_conversation_id')
                        ->where('cu.user_id', (int) $user->id)
                        ->where(function ($q) {
                            $q->whereNull('cu.last_read_at')
                                ->orWhereColumn('m.created_at', '>', 'cu.last_read_at');
                        })
                        ->where(function ($q) use ($user) {
                            $q->whereNull('m.user_id')
                                ->orWhere('m.user_id', '!=', (int) $user->id);
                        })
                        ->count();
                }
            } catch (\Throwable) {
                $unreadMessages = 0;
            }

            $view->with('headerNotifications', $notifications);
            $view->with('headerUnreadNotificationCount', $unreadCount);
            $view->with('headerUnreadMessageCount', $unreadMessages);
        };

        View::composer('layouts.teacher', $composer);
        View::composer('layouts.student', $composer);
        View::composer('layouts.dashboard', $composer);
    }
}
