<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('photoshoot.branding.app_name') . ' ' . config('photoshoot.branding.organization_name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, {{ config('photoshoot.colors.gradient_start') }} 0%, {{ config('photoshoot.colors.gradient_end') }} 100%);
        }
        .warm-gradient {
            background: linear-gradient(135deg, {{ config('photoshoot.colors.warm_gradient_start') }} 0%, {{ config('photoshoot.colors.warm_gradient_end') }} 100%);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-{{ config('photoshoot.colors.primary') }}-50 to-{{ config('photoshoot.colors.secondary') }}-50 min-h-screen">
    <nav class="bg-white shadow-lg border-b-4 border-{{ config('photoshoot.colors.primary') }}-400">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <div class="flex items-center space-x-2 sm:space-x-4 flex-shrink min-w-0">
                    <img src="{{ asset(config('photoshoot.branding.logo_path')) }}" alt="{{ config('photoshoot.branding.organization_name') }}" class="{{ config('photoshoot.branding.logo_height') }} w-auto flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-2xl font-bold text-{{ config('photoshoot.colors.primary') }}-700 truncate">{{ config('photoshoot.branding.app_name') }}</h1>
                        <p class="text-xs sm:text-sm text-gray-600 truncate">{{ config('photoshoot.branding.organization_name') }}</p>
                    </div>
                </div>
                <div class="flex items-center flex-shrink-0 ml-2">
                    @yield('nav-actions')
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-4 pb-[8rem]">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 sm:px-6 py-3 sm:py-4 rounded-r-lg shadow-md mb-4 sm:mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 sm:mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium text-sm sm:text-base">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('message'))
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 px-4 sm:px-6 py-3 sm:py-4 rounded-r-lg shadow-md mb-4 sm:mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 sm:mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium text-sm sm:text-base">{{ session('message') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 sm:px-6 py-3 sm:py-4 rounded-r-lg shadow-md mb-4 sm:mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 sm:mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <ul class="font-medium text-sm sm:text-base">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @if(config('photoshoot.features.show_footer'))
    <footer class="w-full fixed bottom-0 py-3 sm:py-6 bg-white border-t-2 border-{{ config('photoshoot.colors.primary') }}-200">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-600 text-xs sm:text-sm">
                <span class="font-semibold text-{{ config('photoshoot.colors.primary') }}-700">{{ config('photoshoot.branding.organization_name') }}</span> - {{ config('photoshoot.branding.organization_description') }}
            </p>
            <p class="text-gray-500 text-xs mt-1 sm:mt-2">
                {{ config('photoshoot.branding.organization_tagline') }}
            </p>
        </div>
    </footer>
    @endif

    @stack('scripts')
</body>
</html>

