@extends('layouts.app')

@section('title', 'Modifier la Famille')

@section('nav-actions')
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="text-gray-600 hover:text-gray-800">Déconnexion</button>
    </form>
@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.families.show', $family) }}" class="text-blue-500 hover:text-blue-700">&larr; Retour à la Famille</a>
</div>

<div class="max-w-2xl bg-white rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Modifier la Famille</h2>

    <form method="POST" action="{{ route('admin.families.update', $family) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nom de la Famille</label>
            <input
                type="text"
                name="name"
                id="name"
                value="{{ old('name', $family->name) }}"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                required
            >
            @error('name')
                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="directory_name" class="block text-gray-700 text-sm font-bold mb-2">Nom du Répertoire</label>
            <input
                type="text"
                name="directory_name"
                id="directory_name"
                value="{{ $family->directory_name }}"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight"
                disabled
            >
            <p class="text-gray-600 text-xs mt-1">Le nom du répertoire ne peut pas être modifié après la création.</p>
        </div>

        <div class="mb-4">
            <label for="pin" class="block text-gray-700 text-sm font-bold mb-2">PIN</label>
            <input
                type="text"
                name="pin"
                id="pin"
                value="{{ $family->pin }}"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight font-mono"
                disabled
            >
            <p class="text-gray-600 text-xs mt-1">Le PIN ne peut pas être modifié.</p>
        </div>

        <div class="mb-6">
            <label class="flex items-center">
                <input
                    type="checkbox"
                    name="login_enabled"
                    {{ $family->login_enabled ? 'checked' : '' }}
                    class="form-checkbox h-5 w-5 text-blue-600"
                >
                <span class="ml-2 text-gray-700">Activer la connexion pour cette famille</span>
            </label>
        </div>

        <div class="flex items-center justify-between">
            <button
                type="submit"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
            >
                Mettre à Jour la Famille
            </button>
            <a href="{{ route('admin.families.show', $family) }}" class="text-gray-600 hover:text-gray-800">Annuler</a>
        </div>
    </form>
</div>
@endsection

