<?php

return [
    'required'  => 'Il campo :attribute è obbligatorio.',
    'email'     => 'Il campo :attribute deve essere un indirizzo email valido.',
    'confirmed' => 'La conferma di :attribute non coincide.',
    'min'       => [
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
    ],
    'max'       => [
        'string' => 'Il campo :attribute non può superare :max caratteri.',
    ],
    'unique'    => 'Il valore di :attribute è già in uso.',
    'current_password' => 'La password non è corretta.',

    // nomi leggibili dei campi (sostituiscono :attribute)
    'attributes' => [
        'email'                 => 'email',
        'password'              => 'password',
        'password_confirmation' => 'conferma password',
        'current_password'      => 'password attuale',
        'name'                  => 'nome',
        'codice_fiscale'        => 'codice fiscale',
    ],
];
