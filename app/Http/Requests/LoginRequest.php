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
            'patient_id' => ['required', 'integer', 'digits_between:1,8']
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => $this->__('validation.invalid_patient_id'),
            'patient_id.integer' => $this->__('validation.invalid_patient_id'),
            'patient_id.digits_between' => $this->__('validation.invalid_patient_id'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (empty($this->getBirthdate())) {
                $validator->errors()->add('birthdate', $this->__('validation.invalid_date_of_birth'));
            }
        });
    }

    /**
     * Get ISO8601 date string (YYYY-MM-DD) based on birth_day, birth_month and
     * birth_year fields. Day and month "XX" or "00" are replaced by "01".
     * Returns '' if day, month and year don't combine to form a valid date.
     */
    public function getBirthdate(): string
    {
        $year = $this->get('birth_year') ?? '';
        $month = strtoupper($this->get('birth_month') ?? '01');
        $day = strtoupper($this->get('birth_day') ?? '01');

        if (!preg_match('/^\d{4}$/', $year)) {
            return '';
        }
        if (!preg_match('/^(\d{1,2}|[xX]{1,2})?$/', $month)) {
            return '';
        }
        if (!preg_match('/^(\d{1,2}|[xX]{1,2})?$/', $day)) {
            return '';
        }
        if (preg_match('/^[xX]+|0|00$/', $month)) {
            $month = "01";
        }
        if (strlen($month) === 1) {
            $month = "0" . $month;
        }
        if (preg_match('/^[xX]+|0|00$/', $day)) {
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
