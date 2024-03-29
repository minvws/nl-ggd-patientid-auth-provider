<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (TooManyRequestsHttpException $e, Request $request): mixed {
            $session = $request->session();

            App::setLocale($session->get('lang', Config::get('app.locale')));

            if ($session->has('redirect_uri') && $session->has('state')) {
                $qs = http_build_query([
                    'state' => $session->get('state'),
                    'error' => 'cancelled'
                ]);
                $cancel_uri = $session->get('redirect_uri') . '?' . $qs;
            }

            return response()->view('errors.' . $e->getStatusCode(), [
                'cancel_uri' => $cancel_uri ?? null
            ], $e->getStatusCode());
        });
    }
}
