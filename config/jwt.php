<?php

declare(strict_types=1);

return [
    'private_key_path' => '/secrets/jwt.key',
    // TODO
    'iss' => env('CORONACHECK_JWT_ISS', ''),
    'aud' => env('CORONACHECK_JWT_AUD', ''),
    'exp' => env('CORONACHECK_JWT_EXP', 3600),
];
