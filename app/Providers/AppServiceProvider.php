<?php

namespace App\Providers;

use App\Console\Commands\ProcessProd04AcknowledgementSla;
use App\Console\Commands\ProvisionProd04BrowserAccounts;
use App\Console\Commands\SendDesignDeadlineReminders;
use App\Services\BpmRoleAuthorization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SendDesignDeadlineReminders::class,
                ProcessProd04AcknowledgementSla::class,
                ProvisionProd04BrowserAccounts::class,
            ]);
            Schedule::command('prod03:send-design-reminders')->everyMinute()->withoutOverlapping();
            Schedule::command('prod04:process-acknowledgement-sla')
                ->everyMinute()
                ->withoutOverlapping()
                ->when(fn (): bool => is_array(config('prod04.working_calendar')));
        }

        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            $roles = app(BpmRoleAuthorization::class);
            $isMarketing = $user !== null && $roles->has($user, 'MARKETING');
            $isDesigner = $user !== null && $roles->has($user, 'DESIGNER');
            $canAccessProd04 = $user !== null && (
                $isMarketing
                || $roles->has($user, 'ASST_MANAGER_PRODUKSI')
                || $roles->has($user, 'ADMIN_PRODUKSI')
            );
            $canAccessProd05 = $user !== null && (
                $roles->has($user, 'ASST_MANAGER_PRODUKSI')
                || $roles->has($user, 'MANAGER_PRODUKSI')
                || $roles->has($user, 'ADMIN_PRODUKSI')
                || $roles->has($user, 'ADMIN_BPM')
            );

            $view->with([
                'canAccessProd03Briefs' => $isMarketing,
                'canAccessProd03TaskPool' => $isDesigner,
                'canAccessProd03' => $isMarketing || $isDesigner,
                'canAccessProd04' => $canAccessProd04,
                'canAccessProd04Admin' => $user !== null && $roles->has($user, 'ADMIN_BPM'),
                'canAccessProd05' => $canAccessProd05,
                'prod03UnreadNotificationCount' => $user !== null && ($isMarketing || $isDesigner)
                    ? $user->unreadNotifications()->count()
                    : 0,
            ]);
        });
    }
}
