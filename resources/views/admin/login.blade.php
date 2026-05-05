@extends('layouts.app')

@section('title', 'Connexion Administrateur')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 sm:px-0">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6 sm:p-8">
        <h2 class="text-xl sm:text-2xl font-bold text-center mb-4 sm:mb-6">Connexion Administrateur</h2>

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Mot de passe</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror"
                    placeholder="Entrez le mot de passe administrateur"
                    required
                    autofocus
                >
                @error('password')
                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
            >
                Connexion
            </button>
        </form>

        <div class="mt-4 sm:mt-6 text-center">
            <a href="{{ route('family.login') }}" class="text-xs sm:text-sm text-blue-500 hover:text-blue-700">
                Retour à la Connexion Famille
            </a>
        </div>
    </div>
</div>
@endsection

