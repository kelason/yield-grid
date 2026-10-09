<?php

declare(strict_types=1);

return [
    'cta' => 'Buksan ang Crop Insurance',
    'subjects' => [
        'enrollment_window' => 'Malapit nang magbukas ang PCIC enrollment',
        'notice_of_loss_deadline' => 'Ihain ang PCIC Notice of Loss bago ang deadline',
        'claim_followup' => 'Mag-follow up sa iyong PCIC claim',
        'renewal' => 'Malapit nang mapaso ang iyong crop insurance',
    ],
    'lines' => [
        'enrollment_window' => [
            'Ang panahon ng pagtatanim ng :program para sa :season :season_year (:window_label) ay malapit nang magbukas.',
            'Ihanda ang RSBSA stub at requirements, saka kumpletuhin ang enrollment guide sa YieldGrid.',
        ],
        'notice_of_loss_deadline' => [
            'Ang nakasulat na Notice of Loss para sa pinsala noong :loss_date ay dapat makarating sa PCIC bago ang :deadline.',
            'Maghain sa Municipal Agriculturist Office, saka itala ang paghahain sa YieldGrid.',
        ],
        'claim_followup' => [
            'Walang progreso ang iyong claim nitong huli. Makipag-ugnayan sa MAO o PCIC regional office para sa katayuan nito.',
            'I-update ang yugto ng claim sa YieldGrid para manatiling tama ang mga paalala.',
        ],
        'renewal' => [
            'Mapapaso ang iyong crop insurance coverage sa :expires_at.',
            'Magsimula ng bagong enrollment para sa susunod na panahon para manatili kang protektado.',
        ],
    ],
];
