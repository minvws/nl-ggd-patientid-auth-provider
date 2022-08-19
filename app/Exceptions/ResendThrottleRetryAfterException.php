<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ResendThrottleRetryAfterException extends Exception
{
    public function __construct(protected int $retryAfter)
    {
        parent::__construct();
    }

    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        // We don't want the exception to be shown in logs.
        return false;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        App::setLocale($request->session()->get('lang', Config::get('app.locale')));

        return response()->view('errors.resend_throttle', [
            'retry_after' => $this->getRetryAfter(),
            'back_uri' => route('resend')
        ], 429);
    }

    public function getRetryAfter(): Carbon
    {
        return Carbon::createFromTimestamp($this->retryAfter);
    }
}
