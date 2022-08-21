<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Anonymizer;
use App\Exceptions\SendFailure;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerificationRequest;
use App\Models\Code;
use App\Services\CodeGeneratorService;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\OidcParams;
use App\Services\OidcService;
use App\Services\ResendThrottleService;
use App\Services\SmsService;
use App\Exceptions\ContactInfoNotFound;
use App\Services\UserInfo;
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
    protected ResendThrottleService $resendThrottleService;

    public function __construct(
        EmailService $emailService,
        SmsService $smsService,
        CodeGeneratorService $codeGeneratorService,
        InfoRetrievalService $infoRetrievalService,
        OidcService $oidcService,
        ResendThrottleService $resendThrottleService,
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->infoRetrievalService = $infoRetrievalService;
        $this->oidcService = $oidcService;
        $this->resendThrottleService = $resendThrottleService;
    }

    public function login(Request $request): ViewFactory | ViewContract
    {
        $oidcParams = $request->session()->get('oidcparams');

        return view('login', [
            'client_name' => $oidcParams->get('client')->getName(),
            'cancel_uri' => $this->getCancelUri($oidcParams)
        ]);
    }

    public function loginSubmit(LoginRequest $request): RedirectResponse
    {
        // Generate hash
        $hash = $this->codeGeneratorService->createHash(
            $request->get('patient_id'),
            $request->getBirthdate(),
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
            'cancel_uri' => $this->getCancelUri($request->session()->get('oidcparams')),
        ]);
    }

    public function verifySubmit(VerificationRequest $request): RedirectResponse | ViewFactory | ViewContract
    {
        $hash = $request->session()->get('hash');
        if (!$hash) {
            return Redirect::route('start_auth');
        }

        $this->resendThrottleService->reset($hash);

        // Authorization successful, redirect back to client application with auth code
        return $this->oidcService->finishAuthorize($request, $hash);
    }

    public function resend(Request $request): RedirectResponse | ViewFactory | ViewContract
    {
        $verificationType = $request->session()->get('verification_type');

        if (!$verificationType) {
            return Redirect::route('start_auth');
        }

        return view('resend', [
            'verificationType' => $verificationType,
            'hasPhone' => $request->session()->get('has_phone'),
            'hasEmail' => $request->session()->get('has_email')
        ]);
    }

    public function resendSubmit(Request $request): RedirectResponse
    {
        $hash = $request->session()->get('hash');

        if (!$hash) {
            return Redirect::route('start_auth');
        }

        return $this->sendVerificationCodeAndRedirectToVerify($request, $hash);
    }

    protected function generateVerificationCode(UserInfo $userInfo): Code
    {
        // Generate verification code
        $code = $this->codeGeneratorService->generate($userInfo->hash, false);
        if ($code->isExpired()) {
            // When expired (when we asked to resend the code again for instance), generate a new code
            $code = $this->codeGeneratorService->generate($userInfo->hash, true);
        }

        return $code;
    }

    /**
     * @throws ContactInfoNotFound
     */
    protected function getContactInfo(string $hash): UserInfo
    {
        // Fetch phone number and/or email address
        $contactInfo = $this->infoRetrievalService->retrieve($hash);

        // If not contact info is found, redirect back to login form
        if ($contactInfo->isEmpty()) {
            Log::error("sendVerificationCode: empty contact info for " . $hash);

            throw new ContactInfoNotFound();
        }

        return $contactInfo;
    }

    /**
     * @throws SendFailure
     */
    protected function sendVerificationCode(Request $request, UserInfo $userInfo, Code $code, string $method = ""): void
    {
        // Sending to phone has priority, fallback to email if necessary
        if ($userInfo->phoneNumber && $method !== "email") {
            $verificationType = 'sms';
            $result = $this->smsService->send($userInfo->phoneNumber, 'template', ['code' => $code->code]);
            if (! $result) {
                Log::error("sendVerificationCode: send failure");
                throw new SendFailure();
            }

            $anonymizer = new Anonymizer();
            $request->session()->put('verification_sent_to', $anonymizer->phoneNumber($userInfo->phoneNumber));
        } else {
            $verificationType = 'email';
            $result = $this->emailService->send($userInfo->email, 'template', ['code' => $code->code]);
            if (! $result) {
                Log::error("sendVerificationCode: send failure");
                throw new SendFailure();
            }

            $anonymizer = new Anonymizer();
            $request->session()->put('verification_sent_to', $anonymizer->email($userInfo->email));
        }

        // Store verification type so the view can tell the user where to look for the code
        $request->session()->put('verification_type', $verificationType);
    }

    protected function sendVerificationCodeAndRedirectToVerify(Request $request, string $hash): RedirectResponse
    {
        // User "logs in". Code is still valid.
        $code = $this->codeGeneratorService->fetchCodeByHash($hash);
        if ($code !== null && !$code->isExpired()) {
            // User is redirected to /verify as if the code was just sent (no message). Code is not sent again.
            return Redirect::route('verify');
        }

        try {
            // TODO: Check if we can and may cache contact info to prevent multiple lookups
            $userInfo = $this->getContactInfo($hash);
            $request->session()->put('has_phone', $userInfo->hasPhone());
            $request->session()->put('has_email', $userInfo->hasEmail());

            $code = $this->generateVerificationCode($userInfo);

            // Send verification code
            $method = (string) $request->request->get('method');
            $this->sendVerificationCode($request, $userInfo, $code, $method);
        } catch (SendFailure $e) {
            Log::error("authcontroller: send failure: " . $e->getMessage());

            return Redirect::route('start_auth')
                ->withInput()
                ->withErrors([
                    'global' => __('send failed')
                ]);
        } catch (ContactInfoNotFound $e) {
            Log::warning("authcontroller: contact not found: " . $e->getMessage());

            return Redirect::route('start_auth')
                ->withInput()
                ->withErrors([
                    'patient_id' => __('validation.unknown_patient_id'),
                    'birthdate' => __('validation.unknown_date_of_birth'),
                ]);
        }

        return Redirect::route('verify');
    }

    protected function getCancelUri(OidcParams $oidcParams): string
    {
        $qs = http_build_query([
            'state' => $oidcParams->state,
            'error' => 'cancelled'
        ]);
        return  $oidcParams->redirectUri . '?' . $qs;
    }
}
