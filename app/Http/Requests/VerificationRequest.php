<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\CodeGeneratorService;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class VerificationRequest extends FormRequest
{
    protected CodeGeneratorService $codeGeneratorService;

    public function __construct(CodeGeneratorService $codeGeneratorService)
    {
        $this->codeGeneratorService = $codeGeneratorService;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'integer', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => $this->__('validation.required_code'),
            'code.integer' => $this->__('validation.invalid_code'),
            'code.digits' => $this->__('validation.invalid_code'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (!$validator->fails()) {
            $validator->after(function (Validator $validator) {
                if (!$this->isValidCode()) {
                    $validator->errors()->add('code', $this->__('validation.invalid_code'));
                }
            });
        }
    }

    protected function isValidCode(): bool
    {
        $hash = $this->session()->get('hash', '');
        $code = $this->get('code', '');
        return !empty($hash) && !empty($code) && $this->codeGeneratorService->validate($hash, $code);
    }

    protected function __(string $key): string
    {
        $message = __($key);
        return is_string($message) ? $message : '';
    }
}
