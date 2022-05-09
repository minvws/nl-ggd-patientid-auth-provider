<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmationRequest;
use App\Http\Requests\FormRequest;
use App\Services\CodeGeneratorService;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\OidcService;
use App\Services\SmsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class FormController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected EmailService $emailService;
    protected SmsService $smsService;
    protected CodeGeneratorService $codeGeneratorService;
    protected InfoRetrievalService $infoRetrievalService;
    protected OidcService $oidcService;

    public function __construct(
        EmailService $emailService,
        SmsService $smsService,
        CodeGeneratorService $codeGeneratorService,
        InfoRetrievalService $infoRetrievalService,
        OidcService $oidcService
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->infoRetrievalService = $infoRetrievalService;
        $this->oidcService = $oidcService;
    }

    public function entryPoint()
    {
        return view('form');
    }

    /**
     * @param FormRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function submit(FormRequest $request)
    {
        // Fetch phone number and/or email address for this patient id
        $hash = $this->codeGeneratorService->createHash($request->get('patient_id'), $request->get('birthdate'));
        $info = $this->infoRetrievalService->retrieve(($hash));

        $v = Validator::make([], []);
        if (count($info) == 0) {
            $v->getMessageBag()->add('patient_id', 'Patient ID / birthdate combo not found');
                return Redirect::route("form")->withErrors($v);
        }

        // Send code when info is found
        $code = $this->codeGeneratorService->generate($request->get('patient_id'), $request->get('birthdate'));
        $this->sendCode($info['phoneNumber'] ?? '', $info['email'] ?? '', $code->code);

        return view('confirmation')->with('hash', $hash)->with('code', $code->code)->with('errors', $v->getMessageBag());
    }

    /**
     * @param ConfirmationRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function confirmationSubmit(ConfirmationRequest $request)
    {
        $v = Validator::make([], []);

        if ($this->codeGeneratorService->validate($request->get('hash', ''), $request->get('code', ''))) {
            // do stuff when all is ok
            // @TODO: jwt dingetje maken
            dd("Code is ok!");
        }
        $v->getMessageBag()->add('code', 'This code is not correct');

        return view('confirmation')
            ->with('hash', $request->get('hash', ''))
            ->with('errors', $v->getMessageBag()
        );
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
