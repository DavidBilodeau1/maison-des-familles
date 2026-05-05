@extends('layouts.app')

@section('title', 'Détails de la Famille')

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

<div class="bg-white rounded-lg shadow-md p-4 sm:p-8 mb-6">
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-6 gap-4">
        <div class="flex-shrink-0">
            <h2 class="text-2xl font-bold mb-2">{{ $family->name }}</h2>
            <p class="text-gray-600">PIN: <span class="font-mono font-bold text-lg">{{ $family->pin }}</span></p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 flex-wrap">
            <a href="{{ route('admin.families.edit', $family) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center whitespace-nowrap">
                Modifier
            </a>
            <form method="POST" action="{{ route('admin.families.toggle-login', $family) }}" class="inline">
                @csrf
                <button type="submit" class="w-full sm:w-auto bg-{{ $family->login_enabled ? 'red' : 'green' }}-500 hover:bg-{{ $family->login_enabled ? 'red' : 'green' }}-700 text-white font-bold py-2 px-4 rounded whitespace-nowrap">
                    {{ $family->login_enabled ? 'Désactiver' : 'Activer' }} la Connexion
                </button>
            </form>
            @if($family->session_started_at)
                <form method="POST" action="{{ route('admin.families.reset-session', $family) }}" class="inline">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded whitespace-nowrap" onclick="return confirm('Êtes-vous sûr?')">
                        Réinitialiser la Session
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-600">Nom du Répertoire</p>
            <p class="font-semibold break-all">{{ $family->directory_name }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Statut de Connexion</p>
            <p class="font-semibold">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $family->login_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $family->login_enabled ? 'Activée' : 'Désactivée' }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Statut de Sélection</p>
            <p class="font-semibold">
                @if($family->selection_completed)
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Complétée</span>
                @elseif($family->isSessionActive())
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">En Cours</span>
                @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Non Commencée</span>
                @endif
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Info de Session</p>
            <p class="font-semibold text-sm">
                @if($family->session_started_at)
                    Débutée: {{ $family->session_started_at->format('d M Y H:i') }}<br>
                    Expire: {{ $family->session_expires_at->format('d M Y H:i') }}
                @else
                    Non débutée
                @endif
            </p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md p-4 sm:p-8">
    <h3 class="text-xl font-bold mb-4">Photos ({{ $family->photoSelections->count() }} au total, {{ $family->selectedPhotos->count() }} sélectionnées)</h3>

    @if($family->photoSelections->isEmpty())
        <p class="text-gray-600 text-sm sm:text-base">Aucune photo téléversée pour le moment. Téléversez des photos dans le répertoire: <code class="bg-gray-100 px-2 py-1 rounded text-xs break-all">storage/app/photos/uploads/{{ $family->directory_name }}/</code></p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-4">
            @foreach($family->photoSelections as $photo)
                <div class="relative">
                    <img
                        src="{{ $photoUrls[$photo->id] }}"
                        alt="Photo"
                        class="w-full h-24 sm:h-32 object-cover rounded shadow"
                    >
                    @if($photo->is_selected)
                        <div class="absolute top-1 sm:top-2 right-1 sm:right-2 bg-green-500 text-white rounded-full p-1">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif
                    @if($photo->location === 'final_choices')
                        <div class="absolute top-1 sm:top-2 left-1 sm:left-2 bg-blue-500 text-white text-xs px-1 sm:px-2 py-0.5 sm:py-1 rounded">
                            Final
                        </div>
                    @endif
                    <p class="text-xs text-gray-600 mt-1 truncate">{{ $photo->photo_filename }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

