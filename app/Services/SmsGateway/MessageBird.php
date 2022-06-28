<?php

declare(strict_types=1);

namespace App\Services\SmsGateway;

use MessageBird\Client;
use MessageBird\Objects\Message;

class MessageBird implements SmsGatewayInterface
{
    protected string $apiKey;
    protected string $sender;

    public function __construct(string $apiKey, string $sender)
    {
        $this->apiKey = $apiKey;
        $this->sender = $sender;
    }

    public function send(string $phoneNumber, string $template, array $vars): bool
    {
        $client = new Client($this->apiKey);

        $msg = new Message();
        $msg->originator = $this->sender;
        $msg->recipients = array($phoneNumber);
        $msg->body = strval(__(':code is your confirmation code', $vars));

        try {
            $client->messages->create($msg);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
