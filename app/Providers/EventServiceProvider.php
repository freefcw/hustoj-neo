<?php

namespace App\Providers;

use App\Entities\User;
use App\Listeners\DatabaseListener;
use App\Listeners\LoginListener;
use App\Listeners\UserDeletedObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class    => [
            SendEmailVerificationNotification::class,
        ],
        Login::class         => [
            LoginListener::class,
        ],
        Failed::class        => [
            LoginListener::class,
        ],
        QueryExecuted::class => [
            DatabaseListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        User::deleted(UserDeletedObserver::class);
    }
}
