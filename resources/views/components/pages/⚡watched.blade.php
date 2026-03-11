<?php

use App\Enums\MovieStatus;
use App\Models\UserMovie;
use App\Services\RecommendationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Vus')] class extends Component
{
    public string $filter = 'all';
    public string $sortBy = 'watched_at';
    public string $search = '';
    public ?int $editingRatingId = null;
    public int $editingRating = 5;

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function startEditRating(int $userMovieId, int $currentRating): void
    {
        $this->editingRatingId = $userMovieId;
        $this->editingRating = $currentRating ?: 5;
    }

    public function saveRating(RecommendationService $recommendations): void
    {
        $this->validate(['editingRating' => ['required', 'integer', 'min:1', 'max:10']]);

        UserMovie::where('id', $this->editingRatingId)
            ->where('user_id', auth()->id())
            ->update(['rating' => $this->editingRating]);

        $this->editingRatingId = null;
        $recommendations->invalidateCache(auth()->user());
    }

    public function cancelEditRating(): void
    {
        $this->editingRatingId = null;
    }

    public function removeWatched(int $userMovieId, RecommendationService $recommendations): void
    {
        UserMovie::where('id', $userMovieId)
            ->where('user_id', auth()->id())
            ->delete();

        $recommendations->invalidateCache(auth()->user());
    }

    public function with(): array
    {
        $query = UserMovie::forUser(auth()->id())
            ->withStatus(MovieStatus::Watched)
            ->with('movie')
            ->when($this->search, fn ($q) => $q->whereHas('movie', fn ($mq) => $mq->where('title', 'ilike', "%{$this->search}%")))
            ->when($this->filter !== 'all', fn ($q) => $q->whereHas('movie', fn ($mq) => $mq->where('type', $this->filter)));

        if ($this->sortBy === 'rating') {
            $query->orderByDesc('rating');
        } elseif ($this->sortBy === 'title') {
            $query->join('movies', 'user_movies.movie_id', '=', 'movies.id')->orderBy('movies.title');
        } else {
            $query->orderByDesc('watched_at');
        }

        $items = $query->get();
        $avgRating = $items->whereNotNull('rating')->avg('rating');

        return ['items' => $items, 'avgRating' => $avgRating];
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Header --}}
    <div class="flex items-start sm:items-center justify-between gap-4 mb-6 sm:mb-8 flex-wrap">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">Films vus</h1>
            <p class="text-slate-400 mt-0.5 text-sm">Votre historique de visionnage</p>
        </div>
        @if($avgRating)
            <div class="glass rounded-xl px-4 py-3 text-center shrink-0">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-xl sm:text-2xl font-bold text-white">{{ number_format($avgRating, 1) }}</span>
                    <span class="text-slate-500 text-xs">/10</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Note moyenne</p>
            </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="glass rounded-2xl p-3 sm:p-4 mb-6 sm:mb-8 space-y-3 sm:space-y-0 sm:flex sm:gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Rechercher..."
                   class="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
        </div>
        <div class="flex gap-2">
            <div class="flex gap-1 glass rounded-xl p-1 flex-1 sm:flex-none">
                <button wire:click="$set('filter', 'all')"
                        class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $filter === 'all' ? 'bg-white/15 text-white' : 'text-slate-400 hover:text-white' }}">
                    Tous
                </button>
                <button wire:click="$set('filter', 'movie')"
                        class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $filter === 'movie' ? 'bg-white/15 text-white' : 'text-slate-400 hover:text-white' }}">
                    Films
                </button>
                <button wire:click="$set('filter', 'tv')"
                        class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $filter === 'tv' ? 'bg-white/15 text-white' : 'text-slate-400 hover:text-white' }}">
                    Séries
                </button>
            </div>
            <select wire:model.live="sortBy"
                    class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                <option value="watched_at">Récents</option>
                <option value="rating">Mieux notés</option>
                <option value="title">Titre</option>
            </select>
        </div>
    </div>

    <p class="text-xs text-slate-500 mb-4">{{ $items->count() }} {{ $items->count() > 1 ? 'titres vus' : 'titre vu' }}</p>

    @if($items->isEmpty())
        <div class="glass rounded-2xl p-12 sm:p-16 text-center">
            <svg class="w-14 h-14 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.868V15.13a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
            </svg>
            <p class="text-slate-400 text-base font-medium">Aucun film vu</p>
            <p class="text-slate-500 text-sm mt-1">Commencez à marquer des films comme vus</p>
        </div>
    @else
        <div class="space-y-2.5">
            @foreach($items as $userMovie)
                <div wire:key="watched-{{ $userMovie->id }}" class="glass rounded-2xl p-3 sm:p-4 hover:bg-white/5 transition-all">
                    <div class="flex gap-3 sm:gap-4">
                        {{-- Poster --}}
                        <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                           wire:navigate
                           class="shrink-0">
                            @if($userMovie->movie->posterUrl())
                                <img src="{{ $userMovie->movie->posterUrl('w185') }}"
                                     alt="{{ $userMovie->movie->title }}"
                                     loading="lazy"
                                     class="w-12 sm:w-14 rounded-xl object-cover aspect-[2/3]">
                            @else
                                <div class="w-12 sm:w-14 aspect-[2/3] rounded-xl bg-[#242436] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                                    </svg>
                                </div>
                            @endif
                        </a>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                                       wire:navigate
                                       class="font-semibold text-white hover:text-rose-400 transition-colors line-clamp-2 text-sm sm:text-base">
                                        {{ $userMovie->movie->title }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="text-xs text-slate-500">{{ $userMovie->movie->year() }}</span>
                                        <span class="text-xs px-1.5 py-0.5 rounded {{ $userMovie->movie->type === \App\Enums\MediaType::TV ? 'bg-blue-500/20 text-blue-400' : 'bg-rose-500/20 text-rose-400' }}">
                                            {{ $userMovie->movie->type->label() }}
                                        </span>
                                        @if($userMovie->watched_at)
                                            <span class="text-xs text-slate-600 hidden sm:inline">Vu le {{ $userMovie->watched_at->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Rating + delete --}}
                                <div class="flex items-center gap-1.5 shrink-0">
                                    @if($editingRatingId === $userMovie->id)
                                        <div class="flex flex-col items-end gap-2">
                                            <div class="flex items-center gap-0.5"
                                                 x-data="{ hovered: 0 }">
                                                @for($i = 1; $i <= 10; $i++)
                                                    <button type="button"
                                                            @mouseenter="hovered = {{ $i }}"
                                                            @mouseleave="hovered = 0"
                                                            wire:click="$set('editingRating', {{ $i }})"
                                                            class="text-lg leading-none transition-all hover:scale-110"
                                                            :class="(hovered ? hovered : {{ $editingRating }}) >= {{ $i }} ? 'text-amber-400' : 'text-slate-700'">
                                                        ★
                                                    </button>
                                                @endfor
                                                <span class="ml-1.5 text-sm font-bold text-amber-400">{{ $editingRating }}/10</span>
                                            </div>
                                            <div class="flex gap-1.5">
                                                <button wire:click="saveRating"
                                                        class="px-3 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 text-xs font-medium transition-all">
                                                    Enregistrer
                                                </button>
                                                <button wire:click="cancelEditRating"
                                                        class="px-3 py-1 rounded-lg bg-white/5 text-slate-400 hover:text-white text-xs transition-all">
                                                    Annuler
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <button wire:click="startEditRating({{ $userMovie->id }}, {{ $userMovie->rating ?? 0 }})"
                                                class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl {{ $userMovie->rating ? 'bg-amber-500/20 text-amber-400 hover:bg-amber-500/30' : 'bg-white/5 text-slate-400 hover:text-white hover:bg-white/10' }} transition-all">
                                            <svg class="w-3.5 h-3.5" fill="{{ $userMovie->rating ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">{{ $userMovie->rating ? $userMovie->rating.'/10' : 'Noter' }}</span>
                                        </button>
                                        <button wire:click="removeWatched({{ $userMovie->id }})"
                                                wire:confirm="Retirer ce film des vus ?"
                                                class="p-1.5 rounded-xl text-slate-600 hover:text-rose-400 hover:bg-rose-500/10 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($userMovie->notes)
                                <p class="text-xs text-slate-500 mt-2 line-clamp-2">{{ $userMovie->notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
