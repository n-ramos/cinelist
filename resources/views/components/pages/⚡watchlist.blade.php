<?php

use App\Enums\MediaType;
use App\Enums\MovieStatus;
use App\Models\UserMovie;
use App\Services\RecommendationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Ma liste')] class extends Component
{
    public string $filter = 'all';
    public string $sortBy = 'created_at';
    public string $search = '';

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function removeFromWatchlist(int $userMovieId, RecommendationService $recommendations): void
    {
        $userMovie = UserMovie::where('id', $userMovieId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $userMovie->delete();
        $recommendations->invalidateCache(auth()->user());
    }

    public function markAsWatched(int $userMovieId, RecommendationService $recommendations): void
    {
        $userMovie = UserMovie::where('id', $userMovieId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $userMovie->update([
            'status' => MovieStatus::Watched->value,
            'watched_at' => now(),
        ]);

        $recommendations->invalidateCache(auth()->user());
    }

    public function with(): array
    {
        $query = UserMovie::forUser(auth()->id())
            ->withStatus(MovieStatus::Watchlist)
            ->with('movie')
            ->when($this->search, fn ($q) => $q->whereHas('movie', fn ($mq) => $mq->where('title', 'ilike', "%{$this->search}%")))
            ->when($this->filter !== 'all', fn ($q) => $q->whereHas('movie', fn ($mq) => $mq->where('type', $this->filter)));

        if ($this->sortBy === 'priority') {
            $query->orderByDesc('priority');
        } elseif ($this->sortBy === 'title') {
            $query->join('movies', 'user_movies.movie_id', '=', 'movies.id')->orderBy('movies.title');
        } else {
            $query->orderByDesc('created_at');
        }

        $items = $query->get();

        return ['items' => $items];
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Header --}}
    <div class="flex items-start sm:items-center justify-between gap-4 mb-6 sm:mb-8 flex-wrap">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">Ma liste</h1>
            <p class="text-slate-400 mt-0.5 text-sm">Films et séries à regarder</p>
        </div>
        <a href="{{ route('discover') }}" wire:navigate
           class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition-all shadow-lg shadow-rose-500/25 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter
        </a>
    </div>

    {{-- Filters --}}
    <div class="glass rounded-2xl p-3 sm:p-4 mb-6 sm:mb-8 space-y-3 sm:space-y-0 sm:flex sm:gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Rechercher dans ma liste..."
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
                <option value="created_at">Récents</option>
                <option value="priority">Priorité</option>
                <option value="title">Titre</option>
            </select>
        </div>
    </div>

    <p class="text-xs text-slate-500 mb-4">{{ $items->count() }} {{ $items->count() > 1 ? 'titres' : 'titre' }}</p>

    @if($items->isEmpty())
        <div class="glass rounded-2xl p-12 sm:p-16 text-center">
            <svg class="w-14 h-14 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-slate-400 text-base font-medium">Votre liste est vide</p>
            <p class="text-slate-500 text-sm mt-1">Découvrez des films et séries à ajouter</p>
            <a href="{{ route('discover') }}" wire:navigate
               class="inline-flex mt-4 px-6 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition-all">
                Découvrir
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
            @foreach($items as $userMovie)
                <div wire:key="wl-{{ $userMovie->id }}"
                     class="glass rounded-2xl overflow-hidden hover:bg-white/5 transition-all group">
                    <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                       wire:navigate
                       class="flex gap-3 p-3">
                        {{-- Poster --}}
                        <div class="shrink-0 w-14 sm:w-16 rounded-xl overflow-hidden">
                            @if($userMovie->movie->posterUrl())
                                <img src="{{ $userMovie->movie->posterUrl('w185') }}"
                                     alt="{{ $userMovie->movie->title }}"
                                     loading="lazy"
                                     class="w-full aspect-[2/3] object-cover">
                            @else
                                <div class="w-full aspect-[2/3] bg-[#242436] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0 py-0.5">
                            <p class="font-medium text-white text-sm line-clamp-2 leading-snug">{{ $userMovie->movie->title }}</p>
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                <span class="text-xs text-slate-500">{{ $userMovie->movie->year() }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $userMovie->movie->type === \App\Enums\MediaType::TV ? 'bg-blue-500/20 text-blue-400' : 'bg-rose-500/20 text-rose-400' }}">
                                    {{ $userMovie->movie->type->label() }}
                                </span>
                                @if($userMovie->movie->vote_average > 0)
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="text-xs text-amber-400">{{ number_format($userMovie->movie->vote_average, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            @if($userMovie->notes)
                                <p class="text-xs text-slate-500 mt-1.5 line-clamp-2">{{ $userMovie->notes }}</p>
                            @endif
                        </div>
                    </a>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 px-3 pb-3">
                        <button wire:click="markAsWatched({{ $userMovie->id }})"
                                wire:confirm="Marquer comme vu ?"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-medium transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Vu !
                        </button>
                        <button wire:click="removeFromWatchlist({{ $userMovie->id }})"
                                wire:confirm="Retirer de la liste ?"
                                class="p-2 rounded-xl text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
