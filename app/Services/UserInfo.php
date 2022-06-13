<?php

declare(strict_types=1);

namespace App\Services;

class UserInfo
{
    public string $phoneNumber;
    public string $email;

    public function withPhoneNr(string $phoneNr): UserInfo
    {
        $this->phoneNumber = $phoneNr;
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
}
