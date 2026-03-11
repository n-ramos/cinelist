<?php

use App\Enums\MediaType;
use App\Enums\MovieStatus;
use App\Models\Movie;
use App\Models\UserMovie;
use App\Services\RecommendationService;
use App\Services\TmdbService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Détail')] class extends Component
{
    public Movie $movie;
    public ?UserMovie $userMovie = null;
    public string $notes = '';
    public int $rating = 0;

    /** @var array<int, array<string, mixed>> */
    public array $recommendations = [];

    public function mount(string $type, int $tmdbId, TmdbService $tmdb): void
    {
        $mediaType = MediaType::tryFrom($type) ?? MediaType::Movie;
        $movie = $tmdb->getDetails($tmdbId, $mediaType);

        if (! $movie) {
            $this->redirectRoute('discover', navigate: true);

            return;
        }

        $this->movie = $movie;

        if (auth()->check()) {
            $this->userMovie = UserMovie::where('user_id', auth()->id())
                ->where('movie_id', $movie->id)
                ->first();

            if ($this->userMovie) {
                $this->notes = $this->userMovie->notes ?? '';
                $this->rating = $this->userMovie->rating ?? 0;
            }
        }

        $this->recommendations = $tmdb->getRecommendations($tmdbId, $mediaType);
    }

    public function addToWatchlist(RecommendationService $recommendations): void
    {
        $this->updateStatus(MovieStatus::Watchlist, $recommendations);
    }

    public function markAsWatched(RecommendationService $recommendations): void
    {
        $this->updateStatus(MovieStatus::Watched, $recommendations);
    }

    public function addToProposals(RecommendationService $recommendations): void
    {
        $this->updateStatus(MovieStatus::Proposed, $recommendations);
    }

    public function rateMovie(int $rating, RecommendationService $recommendations): void
    {
        $this->rating = max(1, min(10, $rating));

        UserMovie::where('user_id', auth()->id())
            ->where('movie_id', $this->movie->id)
            ->update(['rating' => $this->rating]);

        $this->userMovie?->refresh();
        $recommendations->invalidateCache(auth()->user());
    }

    public function saveNotes(RecommendationService $recommendations): void
    {
        UserMovie::where('user_id', auth()->id())
            ->where('movie_id', $this->movie->id)
            ->update(['notes' => $this->notes ?: null]);

        $this->userMovie?->refresh();
        $recommendations->invalidateCache(auth()->user());
    }

    public function removeFromList(RecommendationService $recommendations): void
    {
        UserMovie::where('user_id', auth()->id())
            ->where('movie_id', $this->movie->id)
            ->delete();

        $this->userMovie = null;
        $recommendations->invalidateCache(auth()->user());
    }

    private function updateStatus(MovieStatus $status, RecommendationService $recommendations): void
    {
        $extra = [];
        if ($status === MovieStatus::Watched) {
            $extra['watched_at'] = now();
        }

        $this->userMovie = UserMovie::updateOrCreate(
            ['user_id' => auth()->id(), 'movie_id' => $this->movie->id],
            array_merge(['status' => $status->value], $extra)
        );

        $recommendations->invalidateCache(auth()->user());
    }
};
?>

<div>
    {{-- Backdrop hero --}}
    <div class="relative h-52 sm:h-80 lg:h-96 overflow-hidden">
        @if($movie->backdropUrl())
            <img src="{{ $movie->backdropUrl() }}"
                 alt="{{ $movie->title }}"
                 class="w-full h-full object-cover">
        @else
            <div class="w-full h-full bg-gradient-to-br from-slate-800 to-slate-900"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0f] via-[#0a0a0f]/50 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#0a0a0f]/70 to-transparent"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24 sm:-mt-40 lg:-mt-52 relative z-10 pb-12">

        {{-- Layout mobile : poster centré + info en dessous | Desktop : côte à côte --}}
        <div class="flex flex-col sm:flex-row gap-5 sm:gap-8">

            {{-- Poster --}}
            <div class="shrink-0 flex justify-center sm:justify-start">
                @if($movie->posterUrl())
                    <img src="{{ $movie->posterUrl('w342') }}"
                         alt="{{ $movie->title }}"
                         class="w-28 sm:w-44 lg:w-52 rounded-2xl shadow-2xl shadow-black/60 border border-white/10">
                @else
                    <div class="w-28 sm:w-44 lg:w-52 aspect-[2/3] rounded-2xl bg-[#1a1a27] border border-white/10 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0 pt-0 sm:pt-10 lg:pt-16">
                {{-- Badges --}}
                <div class="flex items-center gap-2 mb-2 flex-wrap justify-center sm:justify-start">
                    <span class="text-xs px-2 py-1 rounded-lg font-medium {{ $movie->type === MediaType::TV ? 'bg-blue-500/20 text-blue-400' : 'bg-rose-500/20 text-rose-400' }}">
                        {{ $movie->type->label() }}
                    </span>
                    @if($userMovie)
                        <span class="text-xs px-2 py-1 rounded-lg bg-white/10 text-slate-300 font-medium">
                            {{ $userMovie->status->label() }}
                        </span>
                    @endif
                </div>

                {{-- Title --}}
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight text-center sm:text-left">
                    {{ $movie->title }}
                </h1>

                @if($movie->original_title && $movie->original_title !== $movie->title)
                    <p class="text-slate-500 mt-1 text-sm text-center sm:text-left">{{ $movie->original_title }}</p>
                @endif

                {{-- Meta --}}
                <div class="flex items-center gap-3 mt-2.5 flex-wrap justify-center sm:justify-start">
                    @if($movie->year())
                        <span class="text-slate-400 text-sm">{{ $movie->year() }}</span>
                    @endif
                    @if($movie->formattedRuntime())
                        <span class="text-slate-500 text-sm">·</span>
                        <span class="text-slate-400 text-sm">{{ $movie->formattedRuntime() }}</span>
                    @endif
                    @if($movie->vote_average > 0)
                        <span class="text-slate-500 text-sm">·</span>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-amber-400 font-semibold text-sm">{{ number_format($movie->vote_average, 1) }}</span>
                            <span class="text-slate-600 text-xs">/10 ({{ number_format($movie->vote_count) }})</span>
                        </div>
                    @endif
                </div>

                {{-- Overview --}}
                @if($movie->overview)
                    <p class="text-slate-300 mt-4 leading-relaxed text-sm sm:text-base line-clamp-3 sm:line-clamp-none text-center sm:text-left max-w-2xl">
                        {{ $movie->overview }}
                    </p>
                @endif

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2 mt-5 justify-center sm:justify-start">
                    @guest
                        <a href="{{ route('login') }}" wire:navigate
                           class="flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-rose-500/25">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter à ma liste
                        </a>
                        <a href="{{ route('register') }}" wire:navigate
                           class="flex items-center gap-2 px-4 py-2.5 glass hover:bg-white/10 text-slate-300 text-sm font-semibold rounded-xl transition-all border border-white/10">
                            Créer un compte
                        </a>
                    @endguest
                    @auth
                    @if(!$userMovie || $userMovie->status === MovieStatus::Dismissed)
                        <button wire:click="addToWatchlist"
                                class="flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-rose-500/25">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            À ma liste
                        </button>
                        <button wire:click="markAsWatched"
                                class="flex items-center gap-2 px-4 py-2.5 glass hover:bg-white/10 text-white text-sm font-semibold rounded-xl transition-all border border-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Vu
                        </button>
                        <button wire:click="addToProposals"
                                class="flex items-center gap-2 px-4 py-2.5 glass hover:bg-white/10 text-white text-sm font-semibold rounded-xl transition-all border border-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Proposer
                        </button>
                    @elseif($userMovie->status === MovieStatus::Watchlist)
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-rose-500/20 text-rose-400 text-sm font-semibold rounded-xl border border-rose-500/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            Dans ma liste
                        </div>
                        <button wire:click="markAsWatched"
                                class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-sm font-semibold rounded-xl transition-all border border-emerald-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Vu !
                        </button>
                        <button wire:click="removeFromList"
                                class="p-2.5 glass hover:bg-white/10 text-slate-400 hover:text-rose-400 rounded-xl transition-all border border-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @elseif($userMovie->status === MovieStatus::Watched)
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500/20 text-emerald-400 text-sm font-semibold rounded-xl border border-emerald-500/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Vu
                        </div>
                        <button wire:click="removeFromList"
                                class="p-2.5 glass hover:bg-white/10 text-slate-400 hover:text-rose-400 rounded-xl transition-all border border-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @elseif($userMovie->status === MovieStatus::Proposed)
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-amber-500/20 text-amber-400 text-sm font-semibold rounded-xl border border-amber-500/30">
                            Proposition
                        </div>
                        <button wire:click="addToWatchlist"
                                class="flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl transition-all">
                            Ajouter à ma liste
                        </button>
                    @endif
                    @endauth
                </div>

            </div>
        </div>

        {{-- Rating + Notes (quand le film est vu) --}}
        @auth
        @if($userMovie)
        <div class="mt-6 glass rounded-2xl p-4 sm:p-6 space-y-5">

            {{-- Étoiles --}}
            <div>
                <h3 class="text-sm font-semibold text-white mb-3">Votre note</h3>
                <div class="flex items-center gap-1 flex-wrap"
                     x-data="{ hovered: 0 }">
                    @for($i = 1; $i <= 10; $i++)
                        <button type="button"
                                @mouseenter="hovered = {{ $i }}"
                                @mouseleave="hovered = 0"
                                wire:click="rateMovie({{ $i }})"
                                class="text-3xl leading-none transition-all hover:scale-110 focus:outline-none"
                                :class="(hovered ? hovered : {{ $rating }}) >= {{ $i }} ? 'text-amber-400' : 'text-slate-700'">
                            ★
                        </button>
                    @endfor
                    @if($rating > 0)
                        <span class="ml-3 text-base font-bold text-amber-400">
                            {{ $rating }}<span class="text-xs text-slate-500 font-normal">/10</span>
                        </span>
                    @else
                        <span class="ml-3 text-sm text-slate-500">Cliquez pour noter</span>
                    @endif
                </div>
            </div>

            {{-- Séparateur --}}
            <div class="border-t border-white/5"></div>

            {{-- Commentaire --}}
            <div>
                <h3 class="text-sm font-semibold text-white mb-2">Commentaire personnel</h3>
                <textarea wire:model.lazy="notes"
                          wire:blur="saveNotes"
                          rows="3"
                          placeholder="Ajoutez vos impressions, commentaires..."
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all resize-none"></textarea>
            </div>
        </div>
        @endif
        @endauth

        {{-- Genres --}}
        @if(!empty($movie->genre_ids))
        <div class="mt-6">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Genres</h3>
            <div class="flex flex-wrap gap-2">
                @foreach(\App\Models\Genre::whereIn('tmdb_id', $movie->genre_ids)->pluck('name') as $genre)
                    <span class="px-3 py-1 glass rounded-full text-sm text-slate-300 border border-white/10">{{ $genre }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recommandations similaires --}}
        @if(!empty($recommendations))
        <div class="mt-8 sm:mt-10">
            <h3 class="text-base sm:text-lg font-semibold text-white mb-4">Vous pourriez aussi aimer</h3>
            <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-7 gap-2 sm:gap-3">
                @foreach(array_slice($recommendations, 0, 7) as $rec)
                    @php
                        $recTitle = $movie->type === MediaType::TV ? ($rec['name'] ?? $rec['title'] ?? '') : ($rec['title'] ?? $rec['name'] ?? '');
                    @endphp
                    <a href="{{ route('movie.detail', ['type' => $movie->type->value, 'tmdbId' => $rec['id']]) }}"
                       wire:navigate
                       wire:key="rec-{{ $rec['id'] }}"
                       class="group relative card-hover rounded-xl overflow-hidden bg-[#1a1a27]">
                        @if(!empty($rec['poster_path']))
                            <img src="https://image.tmdb.org/t/p/w185{{ $rec['poster_path'] }}"
                                 alt="{{ $recTitle }}"
                                 loading="lazy"
                                 class="w-full aspect-[2/3] object-cover">
                        @else
                            <div class="w-full aspect-[2/3] bg-[#242436]"></div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="absolute bottom-0 p-2">
                                <p class="text-xs font-medium text-white line-clamp-2">{{ $recTitle }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
