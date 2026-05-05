@extends('layouts.app')

@section('title', 'Connexion Famille')

@section('content')
<div class="flex items-center justify-center px-4 sm:px-0">
    <div class="max-w-md w-full">
        <!-- Welcome Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-4 sm:p-6 mb-6 border-t-4 border-{{ config('photoshoot.colors.primary') }}-500">
            <div class="text-center mb-4 sm:mb-6">
                <div class="inline-block p-2 sm:p-3 bg-{{ config('photoshoot.colors.primary') }}-100 rounded-full mb-3 sm:mb-4">
                    <svg class="w-10 h-10 sm:w-12 sm:h-12 text-{{ config('photoshoot.colors.primary') }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold text-{{ config('photoshoot.colors.primary') }}-700 mb-2">{{ config('photoshoot.messages.welcome_title') }}</h2>
                <p class="text-gray-600 text-base sm:text-lg">{{ config('photoshoot.messages.welcome_subtitle') }}</p>
            </div>

            <div class="bg-{{ config('photoshoot.colors.primary') }}-50 border-l-4 border-{{ config('photoshoot.colors.primary') }}-400 p-3 sm:p-4 mb-4 sm:mb-6 rounded">
                <p class="text-xs sm:text-sm text-{{ config('photoshoot.colors.primary') }}-800">
                    <span class="font-semibold">{{ config('photoshoot.messages.login_instructions') }}</span><br>
                    Entrez votre code PIN à {{ config('photoshoot.session.pin_length') }} chiffres pour commencer.
                </p>
            </div>

            <form method="POST" action="{{ route('family.login.submit') }}">
                @csrf

                <div class="mb-4 sm:mb-6">
                    <label for="pin" class="block text-gray-700 text-sm font-bold mb-2 sm:mb-3 text-center">Votre Code PIN</label>
                    <input
                        type="text"
                        name="pin"
                        id="pin"
                        maxlength="{{ config('photoshoot.session.pin_length') }}"
                        pattern="^\d{ {{ config('photoshoot.session.pin_length') }} }$"
                        class="shadow-lg appearance-none border-2 border-{{ config('photoshoot.colors.primary') }}-200 rounded-lg w-full py-2 px-2 text-gray-700 text-center text-2xl sm:text-3xl font-mono leading-tight focus:outline-none focus:border-{{ config('photoshoot.colors.primary') }}-500 focus:ring-2 focus:ring-{{ config('photoshoot.colors.primary') }}-200 @error('pin') border-red-500 @enderror"
                        placeholder="{{ str_repeat('•', config('photoshoot.session.pin_length')) }}"
                        required
                        autofocus
                    >
                    @error('pin')
                        <p class="text-red-500 text-xs sm:text-sm italic mt-2 text-center">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full gradient-bg text-white font-bold py-2 sm:py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 text-base sm:text-lg"
                >
                    {{ config('photoshoot.messages.view_photos_button') }}
                </button>
            </form>
        </div>

        <!-- Admin Link -->
        <div class="text-center">
            <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-{{ config('photoshoot.colors.primary') }}-700 transition-colors">
                @if(config('photoshoot.features.enable_emojis'))🔐 @endif Connexion Administrateur
            </a>
        </div>
    </div>
</div>
@endsection

