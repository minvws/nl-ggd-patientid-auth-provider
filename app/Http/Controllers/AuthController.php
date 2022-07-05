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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
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
        return view('login')->with('client', $request->session()->get('client'));
    }

    public function loginSubmit(LoginRequest $request): RedirectResponse
    {
        // Generate hash
        $hash = $this->codeGeneratorService->createHash(
            $request->get('patient_id'),
            $request->parsedBirthdate(),
        );

        // Try to retrieve user info and send verification code
        try {
            $this->sendVerificationCode($request, $hash);
        } catch (SendFailure $e) {
            Log::error("authcontroller: send failure: " . $e->getMessage());

            $v = Validator::make([], []);
            $v->errors()->add('global', $this->__('send failed'));
            $request->session()->put('_old_input', $request->all());
            return Redirect::route('start_auth')->withErrors($v);
        } catch (ContactInfoNotFound $e) {
            Log::warning("authcontroller: contact not found: " . $e->getMessage());

            $v = Validator::make([], []);
            $v->errors()->add('patient_id', $this->__('validation.unknown_patient_id'));
            $v->errors()->add('birthdate', $this->__('validation.unknown_date_of_birth'));
            $request->session()->put('_old_input', $request->all());
            return Redirect::route('start_auth')->withErrors($v);
        }

        // Store hash in session
        $request->session()->put('hash', $hash);

        return Redirect::route('verify');
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

        // Send verification code
        try {
            $this->sendVerificationCode($request, $hash);
        } catch (SendFailure $e) {
            Log::error("authcontroller: send failure: " . $e->getMessage());

            $v = Validator::make([], []);
            $v->errors()->add('global', $this->__('send failed'));
            $request->session()->put('_old_input', $request->all());
            return Redirect::route('start_auth')->withErrors($v);
        } catch (ContactInfoNotFound $e) {
            Log::warning("authcontroller: contact not found: " . $e->getMessage());

            $v = Validator::make([], []);
            $v->errors()->add('patient_id', $this->__('validation.unknown_patient_id'));
            $v->errors()->add('birthdate', $this->__('validation.unknown_date_of_birth'));
            return Redirect::route('start_auth')->withErrors($v);
        }

        return Redirect::route('verify');
    }

    public function configuration(): JsonResponse
    {
        return response()->json([
            'version' => '3.0',
            'token_endpoint_auth_methods_supported' => [
                'none',
            ],
            'claims_parameter_supported' => true,
            'request_parameter_supported' => false,
            'request_uri_parameter_supported' => true,
            'require_request_uri_registration' => false,
            'grant_types_supported' => [
                'authorization_code',
            ],
            'frontchannel_logout_supported' => false,
            'frontchannel_logout_session_supported' => false,
            'backchannel_logout_supported' => false,
            'backchannel_logout_session_supported' => false,
            'issuer' => route('/'),
            'authorization_endpoint' => route('/authorize'),
            'token_endpoint' => route('/accesstoken'),
            'scopes_supported' => [
                'openid',
            ],
            'response_types_supported' => [
                'code',
            ],
            'response_modes_supported' => [
                'query',
            ],
            'subject_types_supported' => [
                'pairwise',
            ],
            'id_token_signing_alg_values_supported' => [
                'RS256',
            ],
        ]);
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
}
