<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class ResendThrottleRetryAfterException extends Exception
{
    public function __construct(protected int $retryAfter)
    {
        parent::__construct();
    }

    /**
     * Report the exception.
     *
     * @return bool
     */
    public function report(): bool
    {
        // We don't want the exception to be shown in logs.
        return true;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return Response
     */
    public function render(Request $request): Response
    {
        App::setLocale($request->session()->get('lang', Config::get('app.locale')));

        return response()->view('errors.resend_throttle', [
            'retry_after' => $this->getRetryAfter(),
            'back_uri' => route('resend')
        ], BaseResponse::HTTP_TOO_MANY_REQUESTS);
    }

    public function getRetryAfter(): Carbon
    {
        return Carbon::createFromTimestamp($this->retryAfter);
    }
}
