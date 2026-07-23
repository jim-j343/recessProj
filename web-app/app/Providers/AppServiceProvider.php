<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
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
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Every authenticated page shares the navigation layout, so this is
        // the one place to load the bell icon's data instead of repeating
        // it in every controller.
        View::composer('layouts.navigation', function ($view) {
            if (Auth::check()) {
                $unreadNotifications = Notification::where('user_id', Auth::id())
                    ->where('is_read', false)
                    ->with('topic')
                    ->latest()
                    ->take(5)
                    ->get();

                $unreadNotificationsCount = Notification::where('user_id', Auth::id())
                    ->where('is_read', false)
                    ->count();
            } else {
                $unreadNotifications = collect();
                $unreadNotificationsCount = 0;
            }

            $view->with([
                'unreadNotifications'      => $unreadNotifications,
                'unreadNotificationsCount' => $unreadNotificationsCount,
            ]);
        });
    }
}
