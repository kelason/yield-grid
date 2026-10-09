<?php

declare(strict_types=1);

return [
    'required' => 'Ang :attribute ay kailangan.',
    'email' => 'Ang :attribute ay dapat wastong email address.',
    'unique' => 'Ang :attribute ay ginagamit na.',
    'in' => 'Ang napiling :attribute ay hindi wasto.',
    'min' => [
        'array' => 'Ang :attribute ay dapat may hindi bababa sa :min na item.',
        'file' => 'Ang :attribute ay dapat hindi bababa sa :min kilobytes.',
        'numeric' => 'Ang :attribute ay dapat hindi bababa sa :min.',
        'string' => 'Ang :attribute ay dapat hindi bababa sa :min na karakter.',
    ],
    'max' => [
        'array' => 'Ang :attribute ay hindi dapat hihigit sa :max na item.',
        'file' => 'Ang :attribute ay hindi dapat hihigit sa :max kilobytes.',
        'numeric' => 'Ang :attribute ay hindi dapat hihigit sa :max.',
        'string' => 'Ang :attribute ay hindi dapat hihigit sa :max na karakter.',
    ],
];
