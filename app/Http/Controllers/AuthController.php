<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Anonymizer;
use App\Exceptions\SendFailure;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerificationRequest;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View as ViewContract;

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

    public function login(Request $request): ViewFactory | ViewContract
    {
        $qs = http_build_query([
            'state' => $request->session()->get('state'),
            'error' => 'cancelled'
        ]);
        $cancel_uri = $request->session()->get('redirect_uri') . '?' . $qs;

        $oidcParams = $request->session()->get('oidcparams');
        return view('login', [
            'client_name' => $oidcParams->get('client')->getName(),
            'cancel_uri' => $cancel_uri
        ]);
    }

    public function loginSubmit(LoginRequest $request): RedirectResponse
    {
        // Generate hash
        $hash = $this->codeGeneratorService->createHash(
            $request->get('patient_id'),
            $request->parsedBirthdate(),
        );

        // Store hash in session
        $request->session()->put('hash', $hash);

        // Try to retrieve user info and send verification code
        return $this->sendVerificationCodeAndRedirectToVerify($request, $hash);
    }

    public function verify(Request $request): RedirectResponse | ViewFactory | ViewContract
    {
        $verificationType = $request->session()->get('verification_type');
        $sentTo = $request->session()->get('verification_sent_to');

        if (!$verificationType || !$sentTo) {
            return Redirect::route('start_auth');
        }

        return view('verify', [
            'verificationType' => $verificationType,
            'sentTo' => $sentTo,
        ]);
    }

    public function verifySubmit(VerificationRequest $request): RedirectResponse | ViewFactory | ViewContract
    {
        $hash = $request->session()->get('hash');
        if (!$hash) {
            return Redirect::route('start_auth');
        }

        // Authorization successful, redirect back to client application with auth code
        return $this->oidcService->finishAuthorize($request, $hash);
    }

    public function resend(Request $request): RedirectResponse | ViewFactory | ViewContract
    {
        $verificationType = $request->session()->get('verification_type');

        if (!$verificationType) {
            return Redirect::route('start_auth');
        }

        return view('resend', ['verificationType' => $verificationType]);
    }

    public function resendSubmit(Request $request): RedirectResponse
    {
        $hash = $request->session()->get('hash');

        if (!$hash) {
            return Redirect::route('start_auth');
        }

        return $this->sendVerificationCodeAndRedirectToVerify($request, $hash);
    }

    protected function sendVerificationCode(Request $request, string $hash): void
    {
        // Fetch phone number and/or email address
        $contactInfo = $this->infoRetrievalService->retrieve($hash);

        // If not contact info is found, redirect back to login form
        if ($contactInfo->isEmpty()) {
            Log::error("sendVerificationCode: empty contact info for " . $hash);

            throw new ContactInfoNotFound();
        }

        // Generate verification code
        $code = $this->codeGeneratorService->generate($hash, false);
        if ($code->isExpired()) {
            // When expired (when we asked to resend the code again for instance), generate a new code
            $code = $this->codeGeneratorService->generate($hash, true);
        }

        // Sending to phone has priority, fallback to email if necessary
        if ($contactInfo->phoneNumber) {
            $verificationType = 'sms';
            $result = $this->smsService->send($contactInfo->phoneNumber, 'template', ['code' => $code->code]);
            if (! $result) {
                Log::error("sendVerificationCode: send failure");
                throw new SendFailure();
            }

            $anonymizer = new Anonymizer();
            $request->session()->put('verification_sent_to', $anonymizer->phoneNumber($contactInfo->phoneNumber));
        } else {
            $verificationType = 'email';
            $result = $this->emailService->send($contactInfo->email, 'template', ['code' => $code->code]);
            if (! $result) {
                Log::error("sendVerificationCode: send failure");
                throw new SendFailure();
            }

            $anonymizer = new Anonymizer();
            $request->session()->put('verification_sent_to', $anonymizer->email($contactInfo->email));
        }

        // Store verification type so the view can tell the user where to look for the code
        $request->session()->put('verification_type', $verificationType);
    }

    protected function __(string $key): string
    {
        $message = __($key);
        return is_string($message) ? $message : '';
    }

    protected function sendVerificationCodeAndRedirectToVerify(Request $request, string $hash): RedirectResponse
    {
        // Send verification code
        try {
            $this->sendVerificationCode($request, $hash);
        } catch (SendFailure $e) {
            Log::error("authcontroller: send failure: " . $e->getMessage());

            return Redirect::route('start_auth')
                ->withInput()
                ->withErrors([
                    'global' => $this->__('send failed')
                ]);
        } catch (ContactInfoNotFound $e) {
            Log::warning("authcontroller: contact not found: " . $e->getMessage());

            return Redirect::route('start_auth')
                ->withInput()
                ->withErrors([
                    'patient_id' => $this->__('validation.unknown_patient_id'),
                    'birthdate' => $this->__('validation.unknown_date_of_birth'),
                ]);
        }

        return Redirect::route('verify');
    }
}
