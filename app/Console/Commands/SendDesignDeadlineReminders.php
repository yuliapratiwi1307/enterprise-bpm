<?php

namespace App\Console\Commands;

use App\Models\DesignRequest;
use App\Notifications\ProductionWorkflowNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDesignDeadlineReminders extends Command
{
    protected $signature = 'prod03:send-design-reminders';

    protected $description = 'Send in-app PROD-03 deadline and cancellation reminders.';

    public function handle(): int
    {
        $now = now();
        $deadlineWindow = max(1, (int) config('prod03.deadline_reminder_hours', 24));
        $cancellationWindow = max(1, (int) config('prod03.cancellation_reminder_hours', 24));
        $nowForDatabase = $now->format('Y-m-d H:i:sP');
        $deadlineWindowForDatabase = $now->copy()->addHours($deadlineWindow)->format('Y-m-d H:i:sP');
        $cancellationWindowForDatabase = $now->copy()->subHours($cancellationWindow)->format('Y-m-d H:i:sP');

        DesignRequest::query()
            ->with(['currentPic', 'marketingBrief.marketing'])
            ->where(function ($query) use ($nowForDatabase, $deadlineWindowForDatabase, $cancellationWindowForDatabase) {
                $query->where(function ($deadline) use ($nowForDatabase, $deadlineWindowForDatabase) {
                    $deadline->whereNotIn('status', ['COMPLETED', 'CANCELLED', 'CANCELLATION_PENDING_ACK'])
                        ->where(function ($window) use ($nowForDatabase, $deadlineWindowForDatabase) {
                            $window->where(function ($dueSoon) use ($nowForDatabase, $deadlineWindowForDatabase) {
                                $dueSoon->whereNotNull('deadline_at')
                                    ->where('deadline_at', '>', $nowForDatabase)
                                    ->where('deadline_at', '<=', $deadlineWindowForDatabase)
                                    ->whereNull('deadline_reminder_sent_at');
                            })->orWhere(function ($overdue) use ($nowForDatabase) {
                                $overdue->whereNotNull('deadline_at')
                                    ->where('deadline_at', '<=', $nowForDatabase)
                                    ->whereNull('overdue_notified_at');
                            });
                        });
                })->orWhere(function ($cancellation) use ($cancellationWindowForDatabase) {
                    $cancellation->where('status', 'CANCELLATION_PENDING_ACK')
                        ->where('cancellation_requested_at', '<=', $cancellationWindowForDatabase)
                        ->where(function ($lastReminder) use ($cancellationWindowForDatabase) {
                            $lastReminder->whereNull('cancellation_reminder_sent_at')
                                ->orWhere('cancellation_reminder_sent_at', '<=', $cancellationWindowForDatabase);
                        });
                });
            })
            ->orderBy('id')
            ->chunkById(100, function ($requests) use ($now, $cancellationWindow): void {
                foreach ($requests as $candidate) {
                    DB::transaction(function () use ($candidate, $now, $cancellationWindow): void {
                        $request = DesignRequest::query()->whereKey($candidate->id)->lockForUpdate()->first();
                        if ($request === null) {
                            return;
                        }

                        $isCancellationReminder = $request->status === 'CANCELLATION_PENDING_ACK';
                        if ($isCancellationReminder) {
                            $reminderWindow = $now->copy()->subHours($cancellationWindow);
                            if (
                                $request->currentPic === null
                                || $request->cancellation_requested_at === null
                                || $request->cancellation_requested_at->isAfter($reminderWindow)
                                || ($request->cancellation_reminder_sent_at !== null
                                    && $request->cancellation_reminder_sent_at->isAfter($reminderWindow))
                            ) {
                                return;
                            }
                            $request->currentPic->notify(new ProductionWorkflowNotification(
                                'CANCELLATION_REMINDER',
                                "Konfirmasi penerimaan instruksi penghentian Design Request #{$request->id} masih diperlukan.",
                                ['design_request_id' => $request->id],
                            ));
                            $request->cancellation_reminder_sent_at = $now;
                            $request->save();

                            return;
                        }

                        if ($request->deadline_at !== null && $request->deadline_at->isPast()) {
                            if ($request->overdue_notified_at !== null || in_array($request->status, ['COMPLETED', 'CANCELLED'], true)) {
                                return;
                            }
                            $message = "Design Request #{$request->id} telah melewati deadline.";
                            $request->marketingBrief->marketing->notify(new ProductionWorkflowNotification(
                                'DESIGN_REQUEST_OVERDUE',
                                $message,
                                ['design_request_id' => $request->id, 'deadline_at' => $request->deadline_at->toISOString()],
                            ));
                            $request->overdue_notified_at = $now;
                            $request->save();

                            return;
                        }

                        if (
                            $request->deadline_at === null
                            || $request->deadline_at->isPast()
                            || $request->status === 'COMPLETED'
                            || $request->status === 'CANCELLED'
                            || $request->deadline_reminder_sent_at !== null
                        ) {
                            return;
                        }

                        $message = "Deadline Design Request #{$request->id} mendekat ({$request->deadline_at->format('d-m-Y H:i')}).";
                        $details = ['design_request_id' => $request->id, 'deadline_at' => $request->deadline_at->toISOString()];
                        $request->currentPic?->notify(new ProductionWorkflowNotification('DEADLINE_REMINDER', $message, $details));
                        $request->marketingBrief->marketing->notify(new ProductionWorkflowNotification('DEADLINE_REMINDER', $message, $details));
                        $request->deadline_reminder_sent_at = $now;
                        $request->overdue_notified_at = null;
                        $request->save();
                    });
                }
            });

        $this->info('PROD-03 reminders processed.');

        return self::SUCCESS;
    }
}
