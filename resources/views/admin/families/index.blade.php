@extends('layouts.app')

@section('title', 'Gérer les Familles')

@section('nav-actions')
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="text-gray-600 hover:text-gray-800">Déconnexion</button>
    </form>
@endsection

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
    <h2 class="text-2xl font-bold">Familles</h2>
    <div class="flex flex-col sm:flex-row gap-2">
        <form method="POST" action="{{ route('admin.families.create-all-directories') }}">
            @csrf
            <button type="submit" class="w-full sm:w-auto bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-center whitespace-nowrap">
                Créer tous les dossiers
            </button>
        </form>
        <form method="POST" action="{{ route('admin.families.enable-all-logins') }}" onsubmit="return confirm('Activer la connexion pour toutes les familles?')">
            @csrf
            <button type="submit" class="w-full sm:w-auto bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-center whitespace-nowrap">
                Activer tous les accès
            </button>
        </form>
        <a href="{{ route('admin.families.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center whitespace-nowrap">
            Créer une Nouvelle Famille
        </a>
    </div>
</div>

<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">PIN</th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Répertoire</th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Connexion</th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($families as $family)
                <tr>
                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm sm:text-base">{{ $family->name }}</td>
                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap font-mono text-sm hidden sm:table-cell">{{ $family->pin }}</td>
                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">{{ $family->directory_name }}</td>
                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                        <form method="POST" action="{{ route('admin.families.toggle-login', $family) }}">
                            @csrf
                            <button type="submit" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full transition-colors cursor-pointer {{ $family->login_enabled ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                {{ $family->login_enabled ? 'Activée' : 'Désactivée' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                        @if($family->selection_completed)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                Complétée
                            </span>
                        @elseif($family->isSessionActive())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                En Cours
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Non Commencée
                            </span>
                        @endif
                    </td>
                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium">
                        <a href="{{ route('admin.families.show', $family) }}" class="text-blue-600 hover:text-blue-900 mr-2 sm:mr-3">Voir</a>
                        <a href="{{ route('admin.families.edit', $family) }}" class="text-indigo-600 hover:text-indigo-900 mr-2 sm:mr-3 hidden sm:inline">Modifier</a>
                        <form method="POST" action="{{ route('admin.families.destroy', $family) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 hidden sm:inline" onclick="return confirm('Êtes-vous sûr?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 sm:px-6 py-4 text-center text-gray-500 text-sm">Aucune famille trouvée. Créez-en une pour commencer.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
