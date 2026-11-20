<?php

return [
    'admin' => [
        'name' => env('PET_ALERT_ADMIN_NAME', 'Administrador'),
        'email' => env('PET_ALERT_ADMIN_EMAIL', 'admin@petalert.com'),
        'password' => env('PET_ALERT_ADMIN_PASSWORD'),
    ],
    'reception' => [
        'name' => env('PET_ALERT_RECEPCION_NAME', 'Recepcionista'),
        'email' => env('PET_ALERT_RECEPCION_EMAIL', 'recepcion@petalert.com'),
        'password' => env('PET_ALERT_RECEPCION_PASSWORD'),
    ],
    'veterinarian' => [
        'name' => env('PET_ALERT_VETERINARIO_NAME', 'Veterinario'),
        'email' => env('PET_ALERT_VETERINARIO_EMAIL', 'veterinario@petalert.com'),
        'password' => env('PET_ALERT_VETERINARIO_PASSWORD'),
    ],
    'owner' => [
        'name' => env('PET_ALERT_PROPIETARIO_NAME', 'Propietario de pruebas'),
        'email' => env('PET_ALERT_PROPIETARIO_EMAIL', 'propietario@petalert.com'),
        'password' => env('PET_ALERT_PROPIETARIO_PASSWORD'),
    ],
];
