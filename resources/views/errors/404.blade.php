<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Pagina non trovata') }} - 404</title>

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
            <div class="p-4 bg-blue-50 dark:bg-blue-950/40 rounded-full text-blue-600 dark:text-blue-400 ring-8 ring-blue-500/10">
                {{-- Magnifying Glass Icon --}}
                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z"></path>
                </svg>
            </div>
        </div>

        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400 tracking-wide uppercase block mb-1">
            {{ __('errors.not_found') }}
        </span>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mb-3">
            {{ __('errors.page_not_found') }}
        </h1>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">
            {{ $exception->getMessage() ?: __('errors.page_missing_or_removed') }}
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
            <a href="javascript:history.back()"
               class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                {{ __('errors.go_back') }}
            </a>

            <a href="{{ url('/admin') }}"
               class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-500 active:bg-blue-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-sm shadow-blue-500/10">
                {{ __('errors.go_to_dashboard') }}
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"></path>
                </svg>
            </a>
        </div>

    </div>

    <footer class="absolute bottom-6 text-xs text-gray-600">
        &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('errors.all_rights_reserved') }}
    </footer>
</body>
</html>
