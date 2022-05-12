<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmationRequest;
use App\Http\Requests\FormRequest;
use App\Services\CodeGeneratorService;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\JwtService;
use App\Services\OidcService;
use App\Services\SmsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
    protected JwtService $jwtService;

    public function __construct(
        EmailService $emailService,
        SmsService $smsService,
        CodeGeneratorService $codeGeneratorService,
        InfoRetrievalService $infoRetrievalService,
        OidcService $oidcService,
        JwtService $jwtService
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->infoRetrievalService = $infoRetrievalService;
        $this->oidcService = $oidcService;
        $this->jwtService = $jwtService;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function entryPoint(Request $request)
    {
        // Fetch redirect URI and store in session for later use
        $redirectUri = $request->query->get('redirect_uri');
        if (!in_array($redirectUri, config('app.redirect_uris'))) {
            throw new BadRequestHttpException("incorrect redirect uri");
        }
        $request->session()->put('redirect_uri', $redirectUri);

        return view('form');
    }

    /**
     * @param FormRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function submit(FormRequest $request)
    {
        $accessToken = $this->oidcService->fetchTokenFromRequest($request);

        // Fetch phone number and/or email address for this patient id
        $hash = $this->codeGeneratorService->createHash($request->get('patient_id'), $request->get('birthdate'));
        $info = $this->infoRetrievalService->retrieve(($hash));

        $v = Validator::make([], []);
        if (count($info) == 0) {
            $v->getMessageBag()->add('patient_id', 'Patient ID / birthdate combo not found');

            return Redirect::route("entrypoint", ['access_token' => $accessToken])->withErrors($v);
        }

        // Send code when info is found
        $code = $this->codeGeneratorService->generate($request->get('patient_id'), $request->get('birthdate'), false);
        if ($code->isExpired()) {
            // When expired (when we asked to resend the code again for instance), generate a new code
            $code = $this->codeGeneratorService->generate(
                $request->get('patient_id'),
                $request->get('birthdate'),
                true
            );
        }
        $this->sendCode($info['phoneNumber'] ?? '', $info['email'] ?? '', $code->code);

        return view('confirmation')
            ->with('hash', $hash)
            ->with('patient_id', $request->get('patient_id'))
            ->with('birthdate', $request->get('birthdate'))
            ->with('code', $code->code)
            ->with('errors', $v->getMessageBag())
        ;
    }

    /**
     * @param ConfirmationRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|RedirectResponse
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function confirmationSubmit(ConfirmationRequest $request)
    {
        $code = $this->codeGeneratorService->fetchCodeByHash($request->get('hash', ''));

        if ($code && $this->codeGeneratorService->validate($request->get('hash', ''), $request->get('code', ''))) {
            // code is ok, generate jwt token and redirect (back) to corona check site/app
            $jwt = $this->jwtService->generate($code);

            $redirectUri = session()->get('redirect_uri', '');
            return new RedirectResponse($redirectUri . '?token=' . urlencode($jwt));
        }

        $v = Validator::make([], []);
        $v->getMessageBag()->add('code', 'This code is not correct');

        return view('confirmation')
            ->with('hash', $request->get('hash', ''))
            ->with('errors', $v->getMessageBag());
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
