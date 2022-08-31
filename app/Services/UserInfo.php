<?php

declare(strict_types=1);

namespace App\Services;

class UserInfo
{
    public string $hash = '';
    public string $phoneNumber = "";
    public string $email = "";

    public function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public function withPhoneNumber(string $phoneNumber): UserInfo
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function withEmail(string $email): UserInfo
    {
        $this->email = $email;

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->phoneNumber) && empty($this->email);
    }

    public function hasPhone(): bool
    {
        return !empty($this->phoneNumber);
    }

    public function hasEmail(): bool
    {
        return !empty($this->email);
    }
}
