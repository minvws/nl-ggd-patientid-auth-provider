<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'digits_between:1,8'],
            'birthdate' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id' => $this->__('validation.invalid_patient_id'),
            'birthdate' => $this->__('validation.invalid_date_of_birth'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (empty($this->parsedBirthdate())) {
                $validator->errors()->add('birthdate', $this->__('validation.invalid_date_of_birth'));
            }
        });
    }

    /**
     * Parse passport date of birth. Possible formats include:
     * - DD-MM-YYYY
     * - XX-MM-YYYY
     * - XX-XX-YYYY
     * - MM-YYYY
     * - XX-YYYY
     * - YYYY
     * (Where "X" is a literal letter X in the input.)
     *
     * The returned date string, if any, is formatted as ISO8601 (YYYY-MM-DD)
     * with "XX" (if present) replaced by "01" for GGD-GHOR-specific reasons.
     */
    public function parsedBirthdate(): string
    {
        $birthdate = $this->get('birthdate');
        if (!is_string($birthdate)) {
            return '';
        }

        // Split on a few common date separators
        $parts = preg_split('/[\\/\\\\,\\.-]/', strtoupper($birthdate));
        if (!$parts || count($parts) > 3) {
            return '';
        }

        // Reverse the array if YYYY was last
        if (strlen(end($parts)) === 4) {
            $parts = array_reverse($parts);
        }

        $year = $parts[0];
        $month = $parts[1] ?? '01';
        $day = $parts[2] ?? '01';

        if (strlen($year) !== 4 || strlen($month) > 2 || strlen($day) > 2) {
            return '';
        }

        if ($month === "X" || $month === "XX") {
            $month = "01";
        }
        if (strlen($month) === 1) {
            $month = "0" . $month;
        }
        if ($day === "X" || $day === "XX") {
            $day = "01";
        }
        if (strlen($day) === 1) {
            $day = "0" . $day;
        }

        $date = $year . '-' . $month . '-' . $day;
        $c = Carbon::createFromFormat('Y-m-d', $date);
        if (!$c || $year != $c->year || $month != $c->month || $day != $c->day) {
            return  '';
        }

        return $date;
    }

    protected function __(string $key): string
    {
        $message = __($key);
        return is_string($message) ? $message : '';
    }
}
