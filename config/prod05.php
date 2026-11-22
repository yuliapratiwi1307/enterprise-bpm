<?php

return [
    'roles' => [
        'validator' => 'ASST_MANAGER_PRODUKSI',
        'manager' => 'MANAGER_PRODUKSI',
        'admin_produksi' => 'ADMIN_PRODUKSI',
        'bpm_administrator' => 'ADMIN_BPM',
    ],
    'statuses' => [
        'DRAFT' => 'DRAFT',
        'UNDER_REVIEW' => 'UNDER_REVIEW',
        'RETURNED' => 'RETURNED',
        'VALIDATED' => 'VALIDATED',
        'VALIDATED_WITH_NOTES' => 'VALIDATED_WITH_NOTES',
        'PENDING_MANAGER' => 'PENDING_MANAGER',
        'APPROVED_FOR_REVIEW' => 'APPROVED_FOR_REVIEW',
        'APPROVED_CONDITIONAL' => 'APPROVED_CONDITIONAL',
        'ON_HOLD' => 'ON_HOLD',
    ],
    'impact_classifications' => [
        'NO_IMPACT' => 'NO_IMPACT',
        'MINOR' => 'MINOR',
        'MAJOR' => 'MAJOR',
        'CRITICAL' => 'CRITICAL',
        'UNDETERMINED' => 'UNDETERMINED',
    ],
    'manager_decisions' => [
        'APPROVED_FOR_REVIEW' => 'APPROVED_FOR_REVIEW',
        'APPROVED_CONDITIONAL' => 'APPROVED_CONDITIONAL',
        'REJECTED' => 'REJECTED',
    ],
    'activity_control_statuses' => [
        'ACTIVE' => 'ACTIVE',
        'ON_HOLD' => 'ON_HOLD',
        'RELEASED' => 'RELEASED',
    ],
];
