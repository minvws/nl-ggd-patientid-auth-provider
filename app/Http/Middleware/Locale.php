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
        $locale = $request->query->get('lang');
        if ($locale && in_array($locale, Config::get('app.locales'), true)) {
            $request->session()->put('lang', $locale);
        } elseif ($request->session()->has('lang')) {
            $locale = $request->session()->get('lang');
        } else {
            $locale = Config::get('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
