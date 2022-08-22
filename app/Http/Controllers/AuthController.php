<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Anonymizer;
use App\Exceptions\ResendThrottleRetryAfterException;
use App\Exceptions\SendFailure;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerificationRequest;
use App\Models\Code;
use App\Services\CodeGeneratorService;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\OidcParams;
use App\Services\OidcService;
use App\Services\PatientCodeGenerationThrottleService;
use App\Services\SmsService;
use App\Exceptions\ContactInfoNotFound;
use App\Services\UserInfo;
use App\Services\PatientCacheService;
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
    protected PatientCodeGenerationThrottleService $patientCodeGenerationThrottleService;
    protected PatientCacheService $patientCacheService;

    public function __construct(
        EmailService $emailService,
        SmsService $smsService,
        CodeGeneratorService $codeGeneratorService,
        InfoRetrievalService $infoRetrievalService,
        OidcService $oidcService,
        PatientCodeGenerationThrottleService $patientCodeGenerationThrottleService,
        PatientCacheService $patientCacheService,
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->infoRetrievalService = $infoRetrievalService;
        $this->oidcService = $oidcService;
        $this->patientCodeGenerationThrottleService = $patientCodeGenerationThrottleService;
        $this->patientCacheService = $patientCacheService;
    }

    public function login(Request $request): ViewFactory | ViewContract
    {
        $oidcParams = $request->session()->get('oidcparams');

        return view('login', [
            'client_name' => $oidcParams->get('client')->getName(),
            'cancel_uri' => $this->getCancelUri($oidcParams)
        ]);
    }

    /**
     * @throws ResendThrottleRetryAfterException
     */
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
        $patientHash = $request->patientHash();

        $verificationType = $this->patientCacheService->getLastSentMethod($patientHash);
        $sentTo = $this->patientCacheService->getLastSentTo($patientHash);

        if ($verificationType === null || $sentTo === null) {
            $this->patientCacheService->clearCache($patientHash);
            return Redirect::route('start_auth');
        }

        return view('verify', [
            'verificationType' => $verificationType,
            'sentTo' => $sentTo,
            'cancel_uri' => $this->getCancelUri($request->session()->get('oidcparams')),
        ]);
    }

    /**
     * Case: User enters invalid code. Handled by the VerificationRequest
     * Case: User enters valid code. Code is no longer valid. Rate limit is inactive. Handled by the VerificationRequest
     * Case: User enters invalid code, code is invalidated. Rate limit is inactive. Handled by the VerificationRequest
     * Case: User enters invalid code, code is invalidated, rate limit is active. Handled by the VerificationRequest
     * @param VerificationRequest $request
     * @return RedirectResponse|ViewFactory|ViewContract
     */
    public function verifySubmit(VerificationRequest $request): RedirectResponse | ViewFactory | ViewContract
    {
        $hash = $request->patientHash();

        $this->patientCodeGenerationThrottleService->reset($hash);
        $this->patientCacheService->clearCache($hash);

        // Authorization successful, redirect back to client application with auth code
        return $this->oidcService->finishAuthorize($request, $hash);
    }

    public function resend(Request $request): RedirectResponse | ViewFactory | ViewContract
    {
        $patientHash = $request->patientHash();

        $verificationType = $this->patientCacheService->getLastSentMethod($patientHash);
        if ($verificationType === null) {
            $this->patientCacheService->clearCache($patientHash);
            return Redirect::route('start_auth');
        }

        return view('resend', [
            'verificationType' => $verificationType,
            'hasPhone' => $this->patientCacheService->getHasPhone($patientHash),
            'hasEmail' => $this->patientCacheService->getHasEmail($patientHash),
        ]);
    }

    /**
     * @throws ResendThrottleRetryAfterException
     */
    public function resendSubmit(Request $request): RedirectResponse
    {
        $hash = $request->patientHash();

        return $this->sendVerificationCodeAndRedirectToVerify($request, $hash);
    }

    /**
     * @throws ResendThrottleRetryAfterException
     */
    protected function generateVerificationCode(UserInfo $userInfo): Code
    {
        // Generate verification code
        $code = $this->codeGeneratorService->generate($userInfo->hash, false);
        if ($code->isExpired()) {
            $retryAfter = $this->patientCodeGenerationThrottleService->getRetryAfter($userInfo->hash);
            if ($retryAfter !== null) {
                // Case: User "logs in". Code is no longer valid. Rate limit is active.
                // Case: User uses "resend" button. Code is no longer valid. Rate limit is active.
                throw new ResendThrottleRetryAfterException($retryAfter);
            }

            // Case: User "logs in". Code is no longer valid. Rate limit is inactive.
            // When expired (when we asked to resend the code again for instance), generate a new code
            $code = $this->codeGeneratorService->generate($userInfo->hash, true);

            // Register code generated attempt
            $this->patientCodeGenerationThrottleService->attempt($userInfo->hash);

            // Clear sent to, because it is a new code
            $this->patientCacheService->clearSentCache($userInfo->hash);
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
    protected function sendVerificationCode(UserInfo $userInfo, Code $code, string $method = ""): void
    {
        // Sending to phone has priority, fallback to email if necessary
        if ($userInfo->phoneNumber && $method !== "email") {
            $verificationType = 'sms';
            $result = $this->smsService->send($userInfo->phoneNumber, 'template', ['code' => $code->code]);
            if (! $result) {
                Log::error("sendVerificationCode: send failure");
                throw new SendFailure();
            }

            // Store verification type so the view can tell the user where to look for the code
            $anonymizer = new Anonymizer();
            $this->patientCacheService->saveSentTo(
                $userInfo->hash,
                $verificationType,
                $anonymizer->phoneNumber($userInfo->phoneNumber)
            );
        } else {
            $verificationType = 'email';
            $result = $this->emailService->send($userInfo->email, 'template', ['code' => $code->code]);
            if (! $result) {
                Log::error("sendVerificationCode: send failure");
                throw new SendFailure();
            }

            // Store verification type so the view can tell the user where to look for the code
            $anonymizer = new Anonymizer();
            $this->patientCacheService->saveSentTo(
                $userInfo->hash,
                $verificationType,
                $anonymizer->email($userInfo->email)
            );
        }
    }

    /**
     * @throws ResendThrottleRetryAfterException
     * @throws ContactInfoNotFound
     */
    protected function sendVerificationCodeAndRedirectToVerify(Request $request, string $hash): RedirectResponse
    {
        // Case: User "logs in". Code is still valid.
        // Case: User uses "resend" button. Code is still valid. Rate limit is inactive
        $code = $this->codeGeneratorService->fetchCodeByHash($hash);

        $sendMethod = (string) $request->request->get('method', $this->patientCacheService->getLastSentMethod($hash));

        if (
            $code !== null
            && !$code->isExpired()
            && $this->patientCacheService->codeIsSentWith($hash, $sendMethod)
        ) {
            // Case: User is redirected to /verify as if the code was just sent (no message). Code is not sent again.
            if (!$this->patientCacheService->hasPhoneOrEmail($hash)) {
                // If we miss cache, then we will get the contact info and save info to cache
                $this->getContactInfoAndSetCache($hash);
            }

            return Redirect::route('verify');
        }

        try {
            $userInfo = $this->getContactInfoAndSetCache($hash);

            $code = $this->generateVerificationCode($userInfo);

            // Send verification code
            $this->sendVerificationCode($userInfo, $code, $sendMethod);
        } catch (SendFailure $e) {
            Log::error("authcontroller: send failure: " . $e->getMessage());

            return Redirect::route('start_auth')
                ->withInput()
                ->withErrors([
                    'global' => __('send failed')
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

    /**
     * @throws ContactInfoNotFound
     */
    protected function getContactInfoAndSetCache(string $hash): UserInfo
    {
        $userInfo = $this->getContactInfo($hash);

        $this->patientCacheService->setHasPhone($userInfo->hash, $userInfo->hasPhone());
        $this->patientCacheService->setHasEmail($userInfo->hash, $userInfo->hasEmail());

        return $userInfo;
    }
}
