<?php

use App\Enums\MovieStatus;
use App\Models\UserMovie;
use App\Services\RecommendationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Accueil')] class extends Component
{
    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('discover', navigate: true);
        }
    }

    public function with(RecommendationService $recommendations): array
    {
        $user = auth()->user();
        $stats = $recommendations->userStats($user);
        $personalizedRecs = $recommendations->getPersonalizedRecommendations($user, 12);

        $recentWatchlist = UserMovie::forUser($user->id)
            ->withStatus(MovieStatus::Watchlist)
            ->with('movie')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $recentWatched = UserMovie::forUser($user->id)
            ->withStatus(MovieStatus::Watched)
            ->with('movie')
            ->orderByDesc('watched_at')
            ->limit(6)
            ->get();

        $proposals = UserMovie::forUser($user->id)
            ->withStatus(MovieStatus::Proposed)
            ->with('movie')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        return compact('stats', 'personalizedRecs', 'recentWatchlist', 'recentWatched', 'proposals');
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Header --}}
    <div class="mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-3xl font-bold text-white">
            Bonjour, <span class="text-rose-400">{{ auth()->user()->name }}</span> 👋
        </h1>
        <p class="text-slate-400 mt-1 text-sm">Votre tableau de bord cinématographique</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4 mb-8 sm:mb-10">
        <div class="glass rounded-2xl p-3 sm:p-5">
            <div class="flex items-center gap-2 sm:gap-3 mb-1.5 sm:mb-2">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.868V15.13a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm text-slate-400 font-medium">À voir</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white">{{ $stats['total_watchlist'] }}</p>
        </div>

        <div class="glass rounded-2xl p-3 sm:p-5">
            <div class="flex items-center gap-2 sm:gap-3 mb-1.5 sm:mb-2">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm text-slate-400 font-medium">Vus</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white">{{ $stats['total_watched'] }}</p>
        </div>

        <div class="glass rounded-2xl p-3 sm:p-5">
            <div class="flex items-center gap-2 sm:gap-3 mb-1.5 sm:mb-2">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm text-slate-400 font-medium">Note moy.</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white">
                {{ $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '—' }}
                <span class="text-xs sm:text-sm text-slate-500 font-normal">/10</span>
            </p>
        </div>

        <div class="glass rounded-2xl p-3 sm:p-5">
            <div class="flex items-center gap-2 sm:gap-3 mb-1.5 sm:mb-2">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-purple-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm text-slate-400 font-medium">Propositions</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white">{{ $stats['total_proposed'] }}</p>
        </div>
    </div>

    {{-- Top genres --}}
    @if(!empty($stats['top_genres']))
    <div class="mb-8 sm:mb-10">
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-sm sm:text-base font-semibold text-white">Vos genres préférés</h2>
            <span class="text-xs text-slate-500 bg-white/5 rounded-full px-2 py-0.5 hidden sm:inline">Basé sur vos notes</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($stats['top_genres'] as $genre)
                <span class="px-3 py-1.5 rounded-full glass text-xs sm:text-sm font-medium text-slate-200 border border-white/10">
                    {{ $genre }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recommandations --}}
    @if($personalizedRecs->isNotEmpty())
    <div class="mb-8 sm:mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm sm:text-base font-semibold text-white">Recommandations pour vous</h2>
                <p class="text-xs text-slate-500 mt-0.5 hidden sm:block">Calculées selon vos goûts</p>
            </div>
            <a href="{{ route('discover') }}" wire:navigate
               class="text-xs sm:text-sm text-rose-400 hover:text-rose-300 transition-colors flex items-center gap-1">
                Voir plus
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2 sm:gap-3">
            @foreach($personalizedRecs as $rec)
                <a href="{{ route('movie.detail', ['type' => $rec['movie']->type->value, 'tmdbId' => $rec['movie']->tmdb_id]) }}"
                   wire:navigate
                   wire:key="rec-{{ $rec['movie']->id }}"
                   class="group relative card-hover rounded-xl overflow-hidden bg-[#1a1a27]">
                    @if($rec['movie']->posterUrl())
                        <img src="{{ $rec['movie']->posterUrl('w342') }}"
                             alt="{{ $rec['movie']->title }}"
                             loading="lazy"
                             class="w-full aspect-[2/3] object-cover">
                    @else
                        <div class="w-full aspect-[2/3] flex items-center justify-center bg-[#242436]">
                            <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="absolute top-1.5 right-1.5">
                        <div class="w-7 h-7 rounded-full bg-black/70 backdrop-blur-sm flex items-center justify-center border border-white/10">
                            <span class="text-[10px] font-bold text-amber-400">{{ $rec['score'] }}</span>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="absolute bottom-0 p-2">
                            <p class="text-xs font-medium text-white line-clamp-2">{{ $rec['movie']->title }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $rec['movie']->year() }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    @php $hasSidebar = $proposals->isNotEmpty() || $recentWatched->isNotEmpty(); @endphp
    <div class="{{ $hasSidebar ? 'grid grid-cols-1 lg:grid-cols-3 gap-6' : '' }}">

        {{-- Watchlist récente --}}
        <div class="{{ $hasSidebar ? 'lg:col-span-2' : '' }}">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <h2 class="text-sm sm:text-base font-semibold text-white">Ma liste — à voir</h2>
                <a href="{{ route('watchlist') }}" wire:navigate class="text-xs sm:text-sm text-rose-400 hover:text-rose-300 transition-colors">
                    Tout voir →
                </a>
            </div>

            @if($recentWatchlist->isEmpty())
                <div class="flex items-center gap-4 glass rounded-2xl px-5 py-4">
                    <svg class="w-7 h-7 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.868V15.13a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                    </svg>
                    <div>
                        <p class="text-slate-300 text-sm font-medium">Votre liste est vide</p>
                        <a href="{{ route('discover') }}" wire:navigate class="text-xs text-rose-400 hover:text-rose-300 transition-colors">
                            Découvrir des films →
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-4 sm:grid-cols-6 {{ $hasSidebar ? 'lg:grid-cols-4' : 'lg:grid-cols-8' }} gap-2 sm:gap-3">
                    @foreach($recentWatchlist as $userMovie)
                        <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                           wire:navigate
                           wire:key="wl-{{ $userMovie->id }}"
                           class="group relative card-hover rounded-xl overflow-hidden bg-[#1a1a27]">
                            @if($userMovie->movie->posterUrl())
                                <img src="{{ $userMovie->movie->posterUrl('w342') }}"
                                     alt="{{ $userMovie->movie->title }}"
                                     loading="lazy"
                                     class="w-full aspect-[2/3] object-cover">
                            @else
                                <div class="w-full aspect-[2/3] flex items-center justify-center bg-[#242436]">
                                    <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="absolute bottom-0 p-2">
                                    <p class="text-xs font-medium text-white line-clamp-2">{{ $userMovie->movie->title }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Sidebar (propositions + récemment vus) --}}
        @if($hasSidebar)
        <div class="space-y-6">
            @if($proposals->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm sm:text-base font-semibold text-white">Propositions</h2>
                    <a href="{{ route('proposals') }}" wire:navigate class="text-xs sm:text-sm text-rose-400 hover:text-rose-300 transition-colors">
                        Tout voir →
                    </a>
                </div>
                <div class="space-y-2">
                    @foreach($proposals as $userMovie)
                        <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                           wire:navigate
                           wire:key="prop-{{ $userMovie->id }}"
                           class="flex items-center gap-3 glass rounded-xl p-2.5 hover:bg-white/5 transition-all">
                            @if($userMovie->movie->posterUrl())
                                <img src="{{ $userMovie->movie->posterUrl('w92') }}"
                                     alt="{{ $userMovie->movie->title }}"
                                     class="w-9 h-[52px] rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-9 h-[52px] rounded-lg bg-[#242436] shrink-0"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ $userMovie->movie->title }}</p>
                                @if($userMovie->proposed_by)
                                    <p class="text-xs text-slate-500 mt-0.5">Par {{ $userMovie->proposed_by }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            @if($recentWatched->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm sm:text-base font-semibold text-white">Récemment vus</h2>
                    <a href="{{ route('watched') }}" wire:navigate class="text-xs sm:text-sm text-rose-400 hover:text-rose-300 transition-colors">
                        Tout voir →
                    </a>
                </div>
                <div class="space-y-2">
                    @foreach($recentWatched as $userMovie)
                        <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                           wire:navigate
                           wire:key="watched-{{ $userMovie->id }}"
                           class="flex items-center gap-3 glass rounded-xl p-2.5 hover:bg-white/5 transition-all">
                            @if($userMovie->movie->posterUrl())
                                <img src="{{ $userMovie->movie->posterUrl('w92') }}"
                                     alt="{{ $userMovie->movie->title }}"
                                     class="w-8 h-11 rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-8 h-11 rounded-lg bg-[#242436] shrink-0"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ $userMovie->movie->title }}</p>
                                @if($userMovie->rating)
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="text-xs text-amber-400 font-medium">{{ $userMovie->rating }}/10</span>
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
