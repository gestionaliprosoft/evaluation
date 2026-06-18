<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Accesso Negato') }} - 403</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Sincronizza la dark mode con il tema di Filament/Browser
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased flex items-center justify-center min-h-screen h-full primitive">

    <div class="max-w-md w-full p-8 text-center mx-4 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800/50 transition-all duration-300">

        <div class="flex justify-center mb-6">
            <div class="p-4 bg-amber-50 dark:bg-amber-950/40 rounded-full text-amber-600 dark:text-amber-400 ring-8 ring-amber-500/10">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002-2.25v-6.75a2.25 2.25 0 00-2-2.25H6.75a2.25 2.25 0 00-2 2.25v6.75a2.25 2.25 0 002 2.25z"></path>
                </svg>
            </div>
        </div>

        <span class="text-sm font-semibold text-amber-600 dark:text-amber-400 tracking-wide uppercase block mb-1">
            {{ __('errors.forbidden') }}
        </span>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mb-3">
            {{ __('errors.unauthorized_action') }}
        </h1>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">
            {{ $exception->getMessage() ?: __('errors.insufficient_permissions_or_locked') }}
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
            <a href="javascript:history.back()"
               class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                {{ __('errors.go_back') }}
            </a>

            <a href="{{ url('/admin') }}"
               class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 text-sm font-medium text-white bg-amber-600 hover:bg-amber-500 active:bg-amber-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all shadow-sm shadow-amber-500/10">
                {{ __('errors.go_to_dashboard') }}
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </a>
        </div>

    </div>

    <footer class="absolute bottom-6 text-xs text-gray-600">
        &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('errors.all_rights_reserved') }}
    </footer>
</body>
</html>
