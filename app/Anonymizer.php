<?php

declare(strict_types=1);

namespace App;

class Anonymizer
{
    public function email(string $email): string
    {
        // Split user @ domain
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***';
        }

        // Too small, only show asterisks
        if (strlen($parts[0]) <= 4) {
            return
                str_repeat('*', strlen($parts[0])) .
                '@' . $parts[1];
        }

        return
            substr($parts[0], 0, 2) .
            str_repeat('*', strlen($parts[0]) - 4) .
            substr($parts[0], -2) .
            '@' . $parts[1];
    }

    public function phoneNumber(string $phoneNumber): string
    {
        if (strlen($phoneNumber) <= 2) {
            return '****';
        }

        $s = str_repeat('*', strlen($phoneNumber) - 2) . substr($phoneNumber, -2);
        if (strlen($s) < 4) {
            return str_pad($s, 4, '*', STR_PAD_LEFT);
        }

        return $s;
    }
}
