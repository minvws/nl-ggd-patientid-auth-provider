<?php

declare(strict_types=1);

return [
    'sms_service' => env('SMS_SERVICE', 'messagebird'),
    'email_service' => env('EMAIL_SERVICE', 'native'),
    'info_retrieval_service' => env('INFORETRIEVAL_SERVICE', 'yenlo'),
];
