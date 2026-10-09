<?php

declare(strict_types=1);

return [
    'cta' => 'Open Crop Insurance',
    'subjects' => [
        'enrollment_window' => 'PCIC enrollment window opening soon',
        'notice_of_loss_deadline' => 'File your PCIC Notice of Loss before the deadline',
        'claim_followup' => 'Follow up on your PCIC claim',
        'renewal' => 'Your crop insurance is expiring soon',
    ],
    'lines' => [
        'enrollment_window' => [
            'The :season :season_year :program planting window (:window_label) is opening soon.',
            'Prepare your RSBSA stub and requirements, then complete your enrollment guide in YieldGrid.',
        ],
        'notice_of_loss_deadline' => [
            'Your written Notice of Loss for the :loss_date crop damage must reach PCIC by :deadline.',
            'File through your Municipal Agriculturist Office, then record the filing in YieldGrid.',
        ],
        'claim_followup' => [
            'Your claim has seen no progress lately. Check with your MAO or PCIC regional office for its status.',
            'Update the claim stage in YieldGrid so your reminders stay accurate.',
        ],
        'renewal' => [
            'Your crop insurance coverage expires on :expires_at.',
            'Start a new enrollment for the coming season so you stay protected.',
        ],
    ],
];
