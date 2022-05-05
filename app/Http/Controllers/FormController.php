<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmationRequest;
use App\Http\Requests\FormRequest;
use App\Services\CodeGeneratorService;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

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

    /**
     * @param FormRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function submit(FormRequest $request)
    {
        // @TODO: goto yenlo

        $code = $this->codeGeneratorService->generate($request->get('patient_id'), $request->get('birthdate'));
        $this->sendCode($request->get('phone_nr', '') ?? '', $request->get('email', '') ?? '', $code->code);

        $hash = $this->codeGeneratorService->createHash($request->get('patient_id'), $request->get('birthdate'));

        return view('confirmation')->with('hash', $hash);
    }

    public function confirmationSubmit(ConfirmationRequest $request)
    {
        $v = Validator::make([], []);

        if ($this->codeGeneratorService->validate($request->get('hash', ''), $request->get('code', ''))) {
            // do stuff when all is ok
            // @TODO: jwt dingetje maken
            dd("Code is ok!");
        }
        $v->getMessageBag()->add('code', 'This code is not correct');

        return view('confirmation')->with('hash', $request->get('hash', ''))->with('errors', $v->getMessageBag());
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
