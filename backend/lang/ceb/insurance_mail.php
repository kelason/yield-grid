<?php

declare(strict_types=1);

return [
    'cta' => 'Ablihi ang Crop Insurance',
    'subjects' => [
        'enrollment_window' => 'Hapit na muabli ang PCIC enrollment; andama ang RSBSA',
        'notice_of_loss_deadline' => 'Ipasar ang PCIC Notice of Loss sa dili pa ang deadline',
        'claim_followup' => 'Pag-follow up sa imong PCIC claim',
        'renewal' => 'Hapit na mapaso ang imong crop insurance',
    ],
    'lines' => [
        'enrollment_window' => [
            'Ang panahon sa pagpananom sa :program alang sa :season :season_year (:window_label) hapit na muabli.',
            'Andama ang RSBSA stub ug mga gikinahanglan, unya kumpletoha ang enrollment guide sa YieldGrid.',
        ],
        'notice_of_loss_deadline' => [
            'Ang sinulat nga Notice of Loss alang sa kadaot niadtong :loss_date kinahanglan muabot sa PCIC sa dili pa ang :deadline.',
            'Pagpasar sa Municipal Agriculturist Office, unya itala ang pagpasar sa YieldGrid.',
        ],
        'claim_followup' => [
            'Walay kalambuan ang imong claim sukad. Pakigsulti sa MAO o PCIC regional office alang sa kahimtang niini.',
            'I-update ang hugna sa claim sa YieldGrid aron magpabiling sakto ang mga pahinumdom.',
        ],
        'renewal' => [
            'Mapaso ang imong crop insurance coverage sa :expires_at.',
            'Sugdi og bag-ong enrollment alang sa sunod nga panahon aron magpabilin kang protektado.',
        ],
    ],
];
