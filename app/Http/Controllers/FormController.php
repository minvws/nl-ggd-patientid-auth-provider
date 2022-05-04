<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FormRequest;
use App\Services\CodeGeneratorService;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class FormController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected EmailService $emailService;
    protected SmsService $smsService;
    protected CodeGeneratorService $codeGeneratorService;

    public function __construct(
        EmailService $emailService,
        SmsService $smsService,
        CodeGeneratorService $codeGeneratorService
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->codeGeneratorService = $codeGeneratorService;
    }

    public function submit(FormRequest $request): void
    {
        // @TODO: check if patient-id / birthdate is valid

        $code = $this->codeGeneratorService->generate($request->get('patient_id'), $request->get('birthdate'));

        $this->sendCode($request->get('phone_nr', '') ?? '', $request->get('email', '') ?? '', $code->code);
    }

    protected function sendCode(string $phoneNr, string $emailAddr, string $code): void
    {
        // Phone has priority
        if (!empty($phoneNr)) {
            $this->smsService->send($phoneNr, 'template', ['code' => $code]);
            return;
        }

        // Do email
        $this->emailService->send($emailAddr, 'template', ['code' => $code]);
    }
}
