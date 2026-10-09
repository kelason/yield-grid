<?php

declare(strict_types=1);

return [
    'required' => 'Ang :attribute gikinahanglan.',
    'email' => 'Ang :attribute kinahanglan balidong email address.',
    'unique' => 'Ang :attribute gigamit na.',
    'in' => 'Ang gipiling :attribute dili balido.',
    'min' => [
        'array' => 'Ang :attribute kinahanglan dili momenos sa :min ka item.',
        'file' => 'Ang :attribute kinahanglan dili momenos sa :min kilobytes.',
        'numeric' => 'Ang :attribute kinahanglan dili momenos sa :min.',
        'string' => 'Ang :attribute kinahanglan dili momenos sa :min ka karakter.',
    ],
    'max' => [
        'array' => 'Ang :attribute kinahanglan dili molapas sa :max ka item.',
        'file' => 'Ang :attribute kinahanglan dili molapas sa :max kilobytes.',
        'numeric' => 'Ang :attribute kinahanglan dili molapas sa :max.',
        'string' => 'Ang :attribute kinahanglan dili molapas sa :max ka karakter.',
    ],
];
