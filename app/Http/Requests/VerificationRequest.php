<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\CodeGeneratorService;
use App\Services\PatientCacheService;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class VerificationRequest extends FormRequest
{
    protected CodeGeneratorService $codeGeneratorService;
    protected PatientCacheService $patientCacheService;

    public function __construct(
        CodeGeneratorService $codeGeneratorService,
        PatientCacheService $patientCacheService,
    ) {
        parent::__construct();

        $this->codeGeneratorService = $codeGeneratorService;
        $this->patientCacheService = $patientCacheService;
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
        $patientHash = $this->patientHash();
        $code = $this->get('code', '');

        if (empty($code)) {
            return false;
        }

        if (!$this->codeGeneratorService->validate($patientHash, $code)) {
            if ($this->patientCacheService->getCodeValidationAttempts($patientHash) >= 5) {
                $this->codeGeneratorService->expireCodeByHash($patientHash);
            }

            $this->patientCacheService->incrementCodeValidationAttempts($patientHash);
            return false;
        }

        return true;
    }

    protected function __(string $key): string
    {
        $message = __($key);
        return is_string($message) ? $message : '';
    }
}
