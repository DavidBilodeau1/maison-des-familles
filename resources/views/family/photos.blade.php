@php
$watermarkSvg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="240" height="110"><text x="120" y="55" text-anchor="middle" dominant-baseline="middle" fill="white" fill-opacity="0.07" font-family="Georgia, serif" font-size="22" font-style="italic" transform="rotate(-30 120 55)">Épreuve</text></svg>');
@endphp
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
            <div
                class="relative group cursor-pointer photo-item"
                data-photo-id="{{ $photo->id }}"
                data-photo-index="{{ $loop->index }}"
                data-selected="{{ $photo->is_selected ? 'true' : 'false' }}"
            >
                <img
                    src="{{ $photoUrls[$photo->id] }}"
                    alt="Photo"
                    class="w-full h-32 sm:h-40 md:h-48 object-cover rounded-lg shadow-md transition-transform group-hover:scale-105"
                >
                {{-- Selection overlay --}}
                <div class="selection-overlay absolute inset-0 flex items-center justify-center {{ $photo->is_selected ? 'bg-green-500 bg-opacity-50' : 'bg-black bg-opacity-0 group-hover:bg-opacity-30' }} rounded-lg transition-all pointer-events-none">
                    <svg class="selection-check w-12 h-12 sm:w-16 sm:h-16 text-white {{ $photo->is_selected ? '' : 'hidden' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                {{-- Expand / fullscreen button --}}
                <button
                    class="expand-btn absolute bottom-2 right-2 z-10 text-white bg-black bg-opacity-50 hover:bg-opacity-80 rounded-full p-1.5 transition-all focus:outline-none opacity-60 sm:opacity-0 sm:group-hover:opacity-100"
                    data-photo-index="{{ $loop->index }}"
                    title="Voir en grand"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                </button>
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

    {{-- ── Lightbox ──────────────────────────────────────────────────────────── --}}
    <div id="lightbox" class="fixed inset-0 z-50 hidden" style="opacity:0;transition:opacity .2s ease;" aria-modal="true" role="dialog">
        {{-- Backdrop --}}
        <div id="lb-backdrop" class="absolute inset-0 bg-black bg-opacity-95"></div>

        {{-- Close --}}
        <button id="lb-close" class="absolute top-4 right-4 z-20 text-white bg-white bg-opacity-15 hover:bg-opacity-30 rounded-full p-2.5 transition-all focus:outline-none">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Photo counter --}}
        <div id="lb-counter" class="absolute top-4 left-1/2 -translate-x-1/2 z-20 text-white text-sm font-medium bg-black bg-opacity-40 px-4 py-1.5 rounded-full select-none pointer-events-none"></div>

        {{-- Prev --}}
        <button id="lb-prev" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-20 text-white bg-white bg-opacity-10 hover:bg-opacity-25 rounded-full p-3 transition-all focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Next --}}
        <button id="lb-next" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-20 text-white bg-white bg-opacity-10 hover:bg-opacity-25 rounded-full p-3 transition-all focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        {{-- Image area --}}
        <div class="absolute inset-0 flex items-center justify-center" style="padding: 4rem 5rem;">
            <div class="relative flex items-center justify-center w-full h-full">
                <img id="lb-img" src="" alt="Photo"
                    class="max-h-full max-w-full object-contain rounded shadow-2xl"
                    style="transition: opacity .15s ease;"
                >
                {{-- Watermark --}}
                <div class="absolute inset-0 pointer-events-none rounded" style="background-image: url('data:image/svg+xml;base64,{{ $watermarkSvg }}'); background-repeat: repeat;"></div>
                {{-- Selected glow border --}}
                <div id="lb-selected-glow" class="absolute inset-0 rounded pointer-events-none hidden"
                    style="box-shadow: inset 0 0 0 3px rgba(34,197,94,.9), 0 0 40px rgba(34,197,94,.25); background: rgba(34,197,94,.06);">
                </div>
            </div>
        </div>

        {{-- Bottom selection pill --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20">
            <button id="lb-select-btn"
                class="flex items-center gap-2 px-6 py-3 rounded-full font-semibold text-sm shadow-2xl transition-all focus:outline-none active:scale-95 bg-white text-gray-800 hover:bg-gray-100"
            >
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span id="lb-select-text">Sélectionner</span>
            </button>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    // ── Timer ──────────────────────────────────────────────────────────────────
    let timeRemaining = Math.floor({{ $timeRemaining }});
    const timerElement = document.getElementById('timer');
    const selectedCountEl = document.getElementById('selected-count');

    function updateTimer() {
        if (timeRemaining <= 0) { document.getElementById('submit-form').submit(); return; }
        const m = Math.floor(timeRemaining / 60);
        const s = Math.floor(timeRemaining % 60);
        timerElement.textContent = `Temps restant: ${m}:${s.toString().padStart(2, '0')}`;
        if (timeRemaining <= 60) timerElement.classList.add('animate-pulse');
        timeRemaining--;
        setTimeout(updateTimer, 1000);
    }
    updateTimer();

    // ── Photo index ────────────────────────────────────────────────────────────
    const photos = [
        @foreach($photos as $photo)
        { id: {{ $photo->id }}, url: "{{ $photoUrls[$photo->id] }}", selected: {{ $photo->is_selected ? 'true' : 'false' }} },
        @endforeach
    ];

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    async function toggleSelection(photoId) {
        const res = await fetch('{{ route('family.photos.toggle') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ photo_id: photoId }),
        });
        return res.json();
    }

    function syncGridItem(index, isSelected) {
        const item = document.querySelector(`.photo-item[data-photo-index="${index}"]`);
        if (!item) return;
        const overlay = item.querySelector('.selection-overlay');
        const check   = item.querySelector('.selection-check');
        item.dataset.selected = isSelected ? 'true' : 'false';
        if (isSelected) {
            overlay.classList.remove('bg-black', 'bg-opacity-0', 'group-hover:bg-opacity-30');
            overlay.classList.add('bg-green-500', 'bg-opacity-50');
            check.classList.remove('hidden');
        } else {
            overlay.classList.add('bg-black', 'bg-opacity-0', 'group-hover:bg-opacity-30');
            overlay.classList.remove('bg-green-500', 'bg-opacity-50');
            check.classList.add('hidden');
        }
        selectedCountEl.textContent = photos.filter(p => p.selected).length;
    }

    // ── Grid click → toggle ────────────────────────────────────────────────────
    document.querySelectorAll('.photo-item').forEach(item => {
        item.addEventListener('click', async function (e) {
            if (e.target.closest('.expand-btn')) return;
            const index = parseInt(this.dataset.photoIndex);
            const data  = await toggleSelection(photos[index].id);
            if (data.success) {
                photos[index].selected = data.is_selected;
                syncGridItem(index, data.is_selected);
            }
        });
    });

    // ── Lightbox ───────────────────────────────────────────────────────────────
    const lightbox     = document.getElementById('lightbox');
    const lbImg        = document.getElementById('lb-img');
    const lbCounter    = document.getElementById('lb-counter');
    const lbSelectBtn  = document.getElementById('lb-select-btn');
    const lbSelectText = document.getElementById('lb-select-text');
    const lbGlow       = document.getElementById('lb-selected-glow');
    const lbPrev       = document.getElementById('lb-prev');
    const lbNext       = document.getElementById('lb-next');
    let currentIndex   = 0;

    function openLightbox(index) {
        currentIndex = index;
        lightbox.classList.remove('hidden');
        requestAnimationFrame(() => { lightbox.style.opacity = '1'; });
        document.body.style.overflow = 'hidden';
        showPhoto(index);
    }

    function closeLightbox() {
        lightbox.style.opacity = '0';
        setTimeout(() => { lightbox.classList.add('hidden'); }, 200);
        document.body.style.overflow = '';
    }

    function showPhoto(index) {
        const photo = photos[index];
        lbImg.style.opacity = '0';
        lbImg.onload = () => { lbImg.style.opacity = '1'; };
        // If already cached the browser fires load synchronously sometimes
        if (lbImg.complete && lbImg.src === photo.url) lbImg.style.opacity = '1';
        lbImg.src = photo.url;
        lbCounter.textContent = `${index + 1} / ${photos.length}`;
        lbPrev.style.visibility = index === 0 ? 'hidden' : 'visible';
        lbNext.style.visibility = index === photos.length - 1 ? 'hidden' : 'visible';
        refreshLightboxSelection(photo.selected);
    }

    function refreshLightboxSelection(isSelected) {
        if (isSelected) {
            lbSelectBtn.className = 'flex items-center gap-2 px-6 py-3 rounded-full font-semibold text-sm shadow-2xl transition-all focus:outline-none active:scale-95 bg-green-500 hover:bg-green-600 text-white';
            lbSelectText.textContent = 'Désélectionner';
            lbGlow.classList.remove('hidden');
        } else {
            lbSelectBtn.className = 'flex items-center gap-2 px-6 py-3 rounded-full font-semibold text-sm shadow-2xl transition-all focus:outline-none active:scale-95 bg-white hover:bg-gray-100 text-gray-800';
            lbSelectText.textContent = 'Sélectionner';
            lbGlow.classList.add('hidden');
        }
    }

    // Expand buttons
    document.querySelectorAll('.expand-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openLightbox(parseInt(this.dataset.photoIndex));
        });
    });

    // Close
    document.getElementById('lb-close').addEventListener('click', closeLightbox);
    document.getElementById('lb-backdrop').addEventListener('click', closeLightbox);

    // Navigate
    lbPrev.addEventListener('click', e => { e.stopPropagation(); if (currentIndex > 0) showPhoto(--currentIndex); });
    lbNext.addEventListener('click', e => { e.stopPropagation(); if (currentIndex < photos.length - 1) showPhoto(++currentIndex); });

    // Select from lightbox
    lbSelectBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const data = await toggleSelection(photos[currentIndex].id);
        if (data.success) {
            photos[currentIndex].selected = data.is_selected;
            refreshLightboxSelection(data.is_selected);
            syncGridItem(currentIndex, data.is_selected);
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', e => {
        if (lightbox.classList.contains('hidden')) return;
        if (e.key === 'Escape')       closeLightbox();
        if (e.key === 'ArrowLeft'  && currentIndex > 0)               showPhoto(--currentIndex);
        if (e.key === 'ArrowRight' && currentIndex < photos.length - 1) showPhoto(++currentIndex);
    });

    // Touch swipe
    let touchStartX = null;
    lightbox.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    lightbox.addEventListener('touchend', e => {
        if (touchStartX === null) return;
        const dx = e.changedTouches[0].clientX - touchStartX;
        if (Math.abs(dx) > 50) {
            if (dx < 0 && currentIndex < photos.length - 1) showPhoto(++currentIndex);
            if (dx > 0 && currentIndex > 0)                 showPhoto(--currentIndex);
        }
        touchStartX = null;
    }, { passive: true });
</script>
@endpush
