<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * @package App\Http\Middleware
 */
class Locale
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $rawLocale = $request->query->get('lang');
        if ($rawLocale && in_array($rawLocale, Config::get('app.locales'), true)) {
            $locale = $rawLocale;

            $request->session()->put('locale', $locale);
        }

        $locale = $request->session()->get('locale', Config::get('app.locale'));
        App::setLocale($locale);

        return $next($request);
    }
}
