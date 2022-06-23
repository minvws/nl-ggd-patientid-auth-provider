<?php

declare(strict_types=1);

namespace App\Services\SmsGateway;

use MessageBird\Client;
use MessageBird\Objects\Message;

class MessageBird implements SmsGatewayInterface
{
    protected string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function send(string $phoneNumber, string $template, array $vars): bool
    {
        $client = new Client($this->apiKey);

        $msg = new Message();
        $msg->originator = 'Alarmallama';
        $msg->recipients = array($phoneNumber);
        $msg->body = 'Jouw persoonlijke PAP code is ' . $vars['code'];

        try {
            $client->messages->create($msg);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
