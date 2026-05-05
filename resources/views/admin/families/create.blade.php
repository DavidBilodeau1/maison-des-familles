@extends('layouts.app')

@section('title', 'Créer une Famille')

@section('nav-actions')
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="text-gray-600 hover:text-gray-800">Déconnexion</button>
    </form>
@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.families.index') }}" class="text-blue-500 hover:text-blue-700">&larr; Retour aux Familles</a>
</div>

<div class="max-w-2xl bg-white rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Créer une Nouvelle Famille</h2>

    <form method="POST" action="{{ route('admin.families.store') }}">
        @csrf

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nom de la Famille</label>
            <input
                type="text"
                name="name"
                id="name"
                value="{{ old('name') }}"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                placeholder="ex: Famille Tremblay"
                required
            >
            @error('name')
                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="directory_name" class="block text-gray-700 text-sm font-bold mb-2">Nom du Répertoire</label>
            <input
                type="text"
                name="directory_name"
                id="directory_name"
                value="{{ old('directory_name') }}"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('directory_name') border-red-500 @enderror"
                placeholder="ex: famille_tremblay (sans espaces, minuscules)"
                required
            >
            <p class="text-gray-600 text-xs mt-1">Ceci doit correspondre au nom du dossier où vous téléverserez leurs photos.</p>
            @error('directory_name')
                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <button
                type="submit"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
            >
                Créer la Famille
            </button>
            <a href="{{ route('admin.families.index') }}" class="text-gray-600 hover:text-gray-800">Annuler</a>
        </div>
    </form>
</div>
@endsection

