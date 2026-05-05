@extends('layouts.app')

@section('title', 'Sélectionnez vos Photos')

@section('nav-actions')
    <div class="flex items-center space-x-4">
        @if(config('photoshoot.features.show_timer'))
        <div id="timer" class="text-lg font-semibold text-red-600"></div>
        @endif
        <form method="POST" action="{{ route('family.logout') }}">
            @csrf
            <button type="submit" class="text-gray-600 hover:text-gray-800">Déconnexion</button>
        </form>
    </div>
@endsection

@section('content')
<div class="mb-4 sm:mb-6 px-2 sm:px-0">
    <h2 class="text-xl sm:text-2xl font-bold mb-2">Bienvenue, {{ $family->name }}!@if(config('photoshoot.features.enable_emojis')) 👋@endif</h2>
    <p class="text-sm sm:text-base text-gray-600">Sélectionnez vos photos préférées. Vous avez {{ config('photoshoot.session.duration_minutes') }} minutes pour faire votre sélection.</p>
    <p class="text-sm text-gray-500 mt-2">Sélectionnées: <span id="selected-count" class="font-bold">{{ $family->selectedPhotos->count() }}</span> photos</p>
</div>

@if($photos->isEmpty())
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mx-2 sm:mx-0">
        Aucune photo disponible pour le moment. Veuillez contacter l'administrateur.
    </div>
@else
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-4 mb-6 px-2 sm:px-0">
        @foreach($photos as $photo)
            <div class="relative group cursor-pointer photo-item" data-photo-id="{{ $photo->id }}">
                <img
                    src="{{ route('photos.serve', ['family' => $family->directory_name, 'filename' => $photo->photo_filename]) }}"
                    alt="Photo"
                    class="w-full h-32 sm:h-40 md:h-48 object-cover rounded-lg shadow-md transition-transform group-hover:scale-105"
                >
                <div class="absolute inset-0 flex items-center justify-center {{ $photo->is_selected ? 'bg-green-500 bg-opacity-50' : 'bg-black bg-opacity-0 group-hover:bg-opacity-30' }} rounded-lg transition-all">
                    @if($photo->is_selected)
                        <svg class="w-12 h-12 sm:w-16 sm:h-16 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex justify-center px-2 sm:px-0">
        <form method="POST" action="{{ route('family.photos.submit') }}" id="submit-form" class="w-full sm:w-auto">
            @csrf
            <button
                type="submit"
                class="w-full sm:w-auto bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-6 sm:px-8 rounded-lg text-base sm:text-lg"
            >
                Soumettre mes Sélections
            </button>
        </form>
    </div>
@endif
@endsection

@push('scripts')
<script>
    let timeRemaining = Math.floor({{ $timeRemaining }});
    const timerElement = document.getElementById('timer');
    const selectedCountElement = document.getElementById('selected-count');

    // Update timer
    function updateTimer() {
        if (timeRemaining <= 0) {
            document.getElementById('submit-form').submit();
            return;
        }

        const minutes = Math.floor(timeRemaining / 60);
        const seconds = Math.floor(timeRemaining % 60);
        timerElement.textContent = `Temps restant: ${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (timeRemaining <= 60) {
            timerElement.classList.add('animate-pulse');
        }

        timeRemaining--;
        setTimeout(updateTimer, 1000);
    }

    updateTimer();

    // Handle photo selection
    document.querySelectorAll('.photo-item').forEach(item => {
        item.addEventListener('click', async function() {
            const photoId = this.dataset.photoId;

            try {
                const response = await fetch('{{ route('family.photos.toggle') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ photo_id: photoId })
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
</script>
@endpush

