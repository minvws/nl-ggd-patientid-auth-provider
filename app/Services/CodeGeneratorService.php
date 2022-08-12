<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Code;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CodeGeneratorService
{
    protected const MAX_DIGITS = 6;           // Length of the OTP codes

    protected string $hmacKey;      // Hmac key to hash the patient/birthdate
    protected int $expiry;          // Number of seconds each code is valid

    public function __construct(string $hmacKey, int $expiry)
    {
        $this->hmacKey = $hmacKey;
        $this->expiry = $expiry;
    }

    /**
     * Generates a new code or return a current code for the patientid/birthdate
     */
    public function generate(string $hash, bool $regenerate = false): Code
    {
        if ($regenerate) {
            Code::whereHash($hash)->delete();
        }

        return Code::firstOrCreate(
            [ 'hash' => $hash ],
            [
                'hash' => $hash,
                'code' => $this->generateCode(self::MAX_DIGITS),
                'expires_at' => Carbon::now()->addSeconds($this->expiry)->timestamp,
            ]
        );
    }

    /**
     * Validates a code for the given patientid/birthdate hash
     */
    public function validate(string $hash, string $code): bool
    {
        $record = Code::whereHash($hash)->first();
        if (! $record) {
            Log::warning("validate: cannot find record for given hash");
            return false;
        }

        if ($record->code !== $code) {
            Log::warning("validate: code is not valid for given hash");
            return false;
        }

        return ! $record->isExpired();
    }

    /**
     * Returns a code object based on the hash
     */
    public function fetchCodeByHash(string $hash): ?Code
    {
        return Code::whereHash($hash)->first();
    }

    /**
     * Generates unique hash from patient-id and birthdate
     */
    public function createHash(string $patientId, string $birthDate): string
    {
        return hash_hmac('sha256', $patientId . '-' . $birthDate, $this->hmacKey);
    }

    /**
     * Generates a numeric code of $digits length, slightly based on TOTP.
     */
    protected function generateCode(int $digits): string
    {
        $max = (int) pow(10, $digits) - 1;
        $number = random_int(0, $max);
        $code = str_pad((string) $number, $digits, '0');

        return $code;
    }
}
