<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Anonymizer;
use App\Http\Requests\ConfirmationRequest;
use App\Http\Requests\LoginRequest;
use App\Services\CodeGeneratorService;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\OidcService;
use App\Services\SmsService;
use App\Exceptions\ContactInfoNotFound;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthController extends BaseController
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
        OidcService $oidcService,
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->infoRetrievalService = $infoRetrievalService;
        $this->oidcService = $oidcService;
    }

    public function login(Request $request): View
    {
        return view('login');
    }

    public function loginSubmit(LoginRequest $request): RedirectResponse
    {
        $hash = $this->codeGeneratorService->createHash($request->get('patient_id'), $request->get('birthdate'));

        // Send confirmation code
        try {
            $this->sendConfirmationCode($request, $hash);
        } catch (ContactInfoNotFound $e) {
            $v = Validator::make([], []);
            $v->getMessageBag()->add('patient_id', 'Patient ID / birthdate combo not found');
            return Redirect::route('start_auth')->withErrors($v);
        }

        // Store hash in session
        $request->session()->put('hash', $hash);

        return Redirect::route('confirm');
    }

    public function confirm(Request $request): RedirectResponse | View
    {
        $confirmationType = $request->session()->get('confirmation_type');
        $sentTo = $request->session()->get('confirmation_sent_to');

        if (!$confirmationType) {
            return Redirect::route('start_auth');
        }

        return view('confirm', [
            'confirmationType' => $confirmationType,
            'sentTo' => $sentTo,
        ]);
    }

    public function confirmationSubmit(ConfirmationRequest $request): RedirectResponse | View
    {
        $hash = $request->session()->get('hash');
        if (!$hash) {
            return Redirect::route('start_auth');
        }

        if ($this->codeGeneratorService->validate($hash, $request->get('code', ''))) {
            // Authorization successful, redirect back to client application with auth code
            return $this->oidcService->finishAuthorize($request, $hash);
        }

        $confirmationType = $request->session()->get('confirmation_type');
        $sentTo = $request->session()->get('confirmation_sent_to');

        if (!$confirmationType) {
            return Redirect::route('start_auth');
        }

        $v = Validator::make([], []);
        $v->getMessageBag()->add('code', 'This code is not correct');

        return view('confirm', [
            'confirmationType' => $confirmationType,
            'sentTo' => $sentTo,
            'errors' => $v->getMessageBag()
        ]);
    }

    public function resend(Request $request): View|RedirectResponse
    {
        $confirmationType = $request->session()->get('confirmation_type');

        if (!$confirmationType) {
            return Redirect::route('start_auth');
        }

        return view('resend', ['confirmationType' => $confirmationType]);
    }

    public function resendSubmit(Request $request): RedirectResponse
    {
        $hash = $request->session()->get('hash');

        if (!$hash) {
            return Redirect::route('start_auth');
        }

        // Send confirmation code
        try {
            $this->sendConfirmationCode($request, $hash);
        } catch (ContactInfoNotFound $e) {
            $v = Validator::make([], []);
            $v->getMessageBag()->add('patient_id', 'Patient ID / birthdate combo not found');
            return Redirect::route('start_auth')->withErrors($v);
        }

        return Redirect::route('confirm');
    }

    protected function sendConfirmationCode(Request $request, string $hash): void
    {
        // Fetch phone number and/or email address
        $contactInfo = $this->infoRetrievalService->retrieve($hash);

        // If not contact info is found, redirect back to login form
        if ($contactInfo->isEmpty()) {
            throw new ContactInfoNotFound();
        }

        // Generate confirmation code
        $code = $this->codeGeneratorService->generate($hash, false);
        if ($code->isExpired()) {
            // When expired (when we asked to resend the code again for instance), generate a new code
            $code = $this->codeGeneratorService->generate($hash, true);
        }

        // Sending to phone has priority, fallback to email if necessary
        if ($contactInfo->phoneNumber) {
            $confirmationType = 'sms';
            $this->smsService->send($contactInfo->phoneNumber, 'template', ['code' => $code->code]);

            $anonymizer = new Anonymizer();
            $request->session()->put('confirmation_sent_to', $anonymizer->phoneNr($contactInfo->phoneNumber));
        } else {
            $confirmationType = 'email';
            $this->emailService->send($contactInfo->email, 'template', ['code' => $code->code]);

            $anonymizer = new Anonymizer();
            $request->session()->put('confirmation_sent_to', $anonymizer->email($contactInfo->email));
        }

        // Store confirmation type so the view can tell the user where to look for the code
        $request->session()->put('confirmation_type', $confirmationType);
    }
}
