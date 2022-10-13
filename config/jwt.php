<?php

declare(strict_types=1);

return [
    # 1 or more cert paths. Must contain at least the cert below, plus any other certs for cert rollover.
    'jwks_certificate_paths' => explode(',', env('JWKS_CERT_PATHS', '')),

    # Key and certificate paths for signing JWTs
    'certificate_path' => env('JWT_CERT_PATH', ''),
    'private_key_path' => env('JWT_KEY_PATH', ''),

    'iss' => env('CORONACHECK_JWT_ISS', ''),
    'aud' => env('CORONACHECK_JWT_AUD', ''),
    'exp' => env('CORONACHECK_JWT_EXP', 3600),
];
