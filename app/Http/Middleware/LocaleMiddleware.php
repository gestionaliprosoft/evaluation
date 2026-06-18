<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // available language in template array
        $availLocale = collect(config('filament-translations.locals'))->keys()->toArray();
        $availLocale = array_combine($availLocale, $availLocale);

        // set browser language if no user lang in table
        $language = auth()->user()?->lang ?? substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);

        // Locale is enabled and allowed to be change
        if (session()->has('locale') && array_key_exists(session()->get('locale'), $availLocale)) {
            // Set the Laravel locale
            session()->put('locale', $language);
            app()->setLocale(session()->get('locale'));
        } else {
            // first time set temporary locale based on user browser setting
            $tempLocale = in_array($language, $availLocale)
              ? $language
              : config('app.locale');

            session()->put('locale', $tempLocale);
            app()->setLocale($tempLocale);
        }

        return $next($request);
    }
}
