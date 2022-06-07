<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as BaseRequest;

class ConfirmationRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'code' => ['string'],
        ];
    }

    public function messages(): array
    {
        // TODO
        return [];
    }
}
