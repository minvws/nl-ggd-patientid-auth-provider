<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as BaseRequest;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        // TODO
        return [
            'birthdate' => ['string'],
            'patient_id' => ['string'],
        ];
    }

    public function messages(): array
    {
        // TODO
        return [];
    }
}
