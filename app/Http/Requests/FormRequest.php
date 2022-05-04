<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as BaseRequest;

class FormRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'birthdate' => ['string'],
            'patient_id' => ['string'],
            'email' => ['nullable', 'string', 'email', 'required_if:phone_nr,null'],
            'phone_nr' => [ 'nullable', 'string', 'required_if:email,null'],
        ];
    }

    public function messages(): array
    {
        return [
//            'firstName.required_if' => __('Firstname is required when last name is empty'),
//            'lastName.required_if' => __('Lastname is required when first name is empty'),
//            'phoneNr.phone' => __('Phone number must have an international format (ie: +31612345678)'),
        ];
    }
}
