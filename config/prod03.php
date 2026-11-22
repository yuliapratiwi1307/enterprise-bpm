<?php

return [
    'deadline_reminder_hours' => (int) env('PROD03_DEADLINE_REMINDER_HOURS', 24),
    'cancellation_reminder_hours' => (int) env('PROD03_CANCELLATION_REMINDER_HOURS', 24),
];
