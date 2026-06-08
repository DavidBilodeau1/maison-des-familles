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
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <a href="{{ route('admin.families.show', $family) }}" class="text-blue-600 hover:text-blue-900">Voir</a>
                            <a href="{{ route('admin.families.edit', $family) }}" class="text-indigo-600 hover:text-indigo-900 hidden sm:inline">Modifier</a>

                            @if($family->selected_photos_count > 0)
                                <button
                                    type="button"
                                    class="text-purple-600 hover:text-purple-900 whitespace-nowrap"
                                    onclick="openSelectionModal({{ $family->id }}, '{{ route('admin.families.selection-info', $family) }}')"
                                >
                                    📷 {{ $family->selected_photos_count }} photos
                                </button>
                            @endif

                            <form method="POST" action="{{ route('admin.families.destroy', $family) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 hidden sm:inline" onclick="return confirm('Êtes-vous sûr?')">Supprimer</button>
                            </form>
                        </div>
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

{{-- ── Selection modal ────────────────────────────────────────────────────── --}}
<div id="selection-modal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeSelectionModal()"></div>

    {{-- Panel --}}
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height: 80vh;">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h3 id="modal-family-name" class="text-lg font-bold text-gray-900"></h3>
                    <p id="modal-subtitle" class="text-sm text-gray-500"></p>
                </div>
                <button onclick="closeSelectionModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Loading state --}}
            <div id="modal-loading" class="flex items-center justify-center py-12">
                <svg class="animate-spin w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Chargement…</span>
            </div>

            {{-- File list --}}
            <ul id="modal-file-list" class="hidden overflow-y-auto divide-y divide-gray-100 flex-1 px-2"></ul>

            {{-- Footer --}}
            <div id="modal-footer" class="hidden flex-shrink-0 flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                <button
                    onclick="copyFilenames()"
                    class="text-sm text-gray-600 hover:text-gray-900 underline"
                >
                    Copier les noms
                </button>
                <div class="flex gap-2">
                    <button onclick="closeSelectionModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg text-sm transition-colors">
                        Fermer
                    </button>
                    <a id="modal-download-btn" href="#" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors whitespace-nowrap">
                        ⬇ Télécharger ZIP
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let modalPhotos = [];

    async function openSelectionModal(familyId, infoUrl) {
        const modal        = document.getElementById('selection-modal');
        const loading      = document.getElementById('modal-loading');
        const fileList     = document.getElementById('modal-file-list');
        const footer       = document.getElementById('modal-footer');
        const nameEl       = document.getElementById('modal-family-name');
        const subtitleEl   = document.getElementById('modal-subtitle');
        const downloadBtn  = document.getElementById('modal-download-btn');

        // Reset
        loading.classList.remove('hidden');
        fileList.classList.add('hidden');
        footer.classList.add('hidden');
        fileList.innerHTML = '';
        nameEl.textContent = '…';
        subtitleEl.textContent = '';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        try {
            const res  = await fetch(infoUrl);
            const data = await res.json();

            modalPhotos = data.photos;
            nameEl.textContent    = data.name;
            subtitleEl.textContent = `${data.photos.length} photo${data.photos.length !== 1 ? 's' : ''} sélectionnée${data.photos.length !== 1 ? 's' : ''}`;
            downloadBtn.href      = data.download_url;

            data.photos.forEach(photo => {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 group';
                li.innerHTML = `
                    <span class="text-sm text-gray-800 font-mono truncate pr-3">${photo.filename}</span>
                    <a href="${photo.url}" target="_blank" rel="noopener"
                       class="flex-shrink-0 text-xs text-blue-600 hover:text-blue-800 opacity-0 group-hover:opacity-100 transition-opacity">
                        Ouvrir ↗
                    </a>`;
                fileList.appendChild(li);
            });

            loading.classList.add('hidden');
            fileList.classList.remove('hidden');
            footer.classList.remove('hidden');
        } catch (e) {
            loading.innerHTML = '<p class="text-red-500 text-sm">Erreur lors du chargement.</p>';
        }
    }

    function closeSelectionModal() {
        document.getElementById('selection-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function copyFilenames() {
        const text = modalPhotos.map(p => p.filename).join('\n');
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.target;
            btn.textContent = 'Copié ✓';
            setTimeout(() => { btn.textContent = 'Copier les noms'; }, 2000);
        });
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSelectionModal();
    });
</script>
@endpush
