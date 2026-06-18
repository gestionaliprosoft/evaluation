<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Server Error') }} - 500</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"; }
    </style>
</head>
<body class="flex h-full flex-col items-center justify-center justify-items-center p-6 antialiased">

    <div class="text-center max-w-md">
        <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-500 mb-6">
            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>

        <p class="text-sm font-semibold uppercase tracking-wide text-amber-500">{{__('errors.internal_server_error')}}</p>

        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
            {{__('errors.something_went_wrong')}}
        </h1>

        <p class="mt-4 text-base text-gray-400">
            {{__('errors.server_error_description')}}
        </p>

        <div class="mt-8">
            <a href="{{ url('/admin') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-sm hover:bg-amber-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 transition-colors duration-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                {{ __('errors.go_to_dashboard') }}
            </a>
        </div>
    </div>

    <footer class="absolute bottom-6 text-xs text-gray-600">
        &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('errors.all_rights_reserved') }}
    </footer>
</body>
</html>
