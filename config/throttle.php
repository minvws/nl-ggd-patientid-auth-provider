<?php

declare(strict_types=1);

return [
    'requests' => env('THROTTLE_NUM_REQUESTS', '3'),
    'period' => env('THROTTLE_PERIOD_MINUTES', '10'),
];
