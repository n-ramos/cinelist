<?php

use App\Enums\MediaType;
use App\Services\TmdbService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Découvrir')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $mediaType = 'all';

    #[Url]
    public string $sortBy = 'popularity.desc';

    public int $page = 1;
    public bool $hasMore = true;

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    /** @var array<int, array<string, mixed>> */
    public array $trending = [];

    public bool $isLoading = false;

    public function mount(TmdbService $tmdb): void
    {
        $this->trending = $tmdb->trending('week');
        $this->loadResults($tmdb);
    }

    public function updatedSearch(TmdbService $tmdb): void
    {
        $this->page = 1;
        $this->results = [];
        $this->loadResults($tmdb);
    }

    public function updatedMediaType(TmdbService $tmdb): void
    {
        $this->page = 1;
        $this->results = [];
        $this->loadResults($tmdb);
    }

    public function updatedSortBy(TmdbService $tmdb): void
    {
        $this->page = 1;
        $this->results = [];
        $this->loadResults($tmdb);
    }

    public function loadMore(TmdbService $tmdb): void
    {
        $this->page++;
        $this->loadResults($tmdb);
    }

    private function loadResults(TmdbService $tmdb): void
    {
        if (! empty($this->search)) {
            $data = $tmdb->search($this->search, $this->page);
            $newResults = array_values($data['results'] ?? []);
            $this->hasMore = $this->page < ($data['total_pages'] ?? 1);
        } else {
            $type = $this->mediaType !== 'all' ? MediaType::from($this->mediaType) : MediaType::Movie;
            $data = $tmdb->discover($type, ['sort_by' => $this->sortBy], $this->page);
            $newResults = $data['results'] ?? [];

            foreach ($newResults as &$item) {
                $item['media_type'] = $this->mediaType !== 'all' ? $this->mediaType : 'movie';
            }
            unset($item);

            $this->hasMore = $this->page < ($data['total_pages'] ?? 1);
        }

        if ($this->page === 1) {
            $this->results = $newResults;
        } else {
            $this->results = array_merge($this->results, $newResults);
        }
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-white">Découvrir</h1>
        <p class="text-slate-400 mt-1 text-sm">Explorez des milliers de films et séries</p>
    </div>

    {{-- Search & Filters --}}
    <div class="glass rounded-2xl p-3 sm:p-4 mb-6 sm:mb-8 space-y-3">
        {{-- Search --}}
        <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Rechercher un film, une série..."
                   class="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500/50 transition-all">
        </div>

        {{-- Filters row --}}
        <div class="flex gap-2">
            <select wire:model.live="mediaType"
                    class="flex-1 bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                <option value="all">Films & Séries</option>
                <option value="movie">Films</option>
                <option value="tv">Séries</option>
            </select>

            <select wire:model.live="sortBy"
                    class="flex-1 bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                <option value="popularity.desc">Populaires</option>
                <option value="vote_average.desc">Mieux notés</option>
                <option value="release_date.desc">Récents</option>
                <option value="revenue.desc">Box-office</option>
            </select>
        </div>
    </div>

    {{-- Trending (quand pas de recherche) --}}
    @if(empty($search) && !empty($trending))
    <div class="mb-8">
        <h2 class="text-sm sm:text-base font-semibold text-white mb-3 flex items-center gap-2">
            🔥 <span>Tendances cette semaine</span>
        </h2>
        <div class="flex gap-3 overflow-x-auto pb-3 scrollbar-hide -mx-4 px-4 sm:mx-0 sm:px-0">
            @foreach(array_slice($trending, 0, 12) as $item)
                @php
                    $type = $item['media_type'] ?? 'movie';
                    $title = $type === 'tv' ? ($item['name'] ?? $item['title'] ?? '') : ($item['title'] ?? $item['name'] ?? '');
                @endphp
                <a href="{{ route('movie.detail', ['type' => $type, 'tmdbId' => $item['id']]) }}"
                   wire:navigate
                   wire:key="trending-{{ $item['id'] }}"
                   class="group relative shrink-0 w-24 sm:w-28 card-hover rounded-xl overflow-hidden bg-[#1a1a27]">
                    @if(!empty($item['poster_path']))
                        <img src="https://image.tmdb.org/t/p/w342{{ $item['poster_path'] }}"
                             alt="{{ $title }}"
                             loading="lazy"
                             class="w-full aspect-[2/3] object-cover">
                    @else
                        <div class="w-full aspect-[2/3] bg-[#242436] flex items-center justify-center">
                            <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="absolute top-1.5 left-1.5">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $type === 'tv' ? 'bg-blue-500/80' : 'bg-rose-500/80' }} text-white backdrop-blur-sm">
                            {{ $type === 'tv' ? 'Série' : 'Film' }}
                        </span>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="absolute bottom-0 p-2">
                            <p class="text-xs font-medium text-white line-clamp-2">{{ $title }}</p>
                            @if(!empty($item['vote_average']))
                                <div class="flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="text-xs text-amber-400">{{ number_format($item['vote_average'], 1) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Results header --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm sm:text-base font-semibold text-white">
            @if(!empty($search))
                Résultats pour « {{ $search }} »
            @else
                {{ match($sortBy) {
                    'popularity.desc' => '🎬 Films populaires',
                    'vote_average.desc' => '⭐ Mieux notés',
                    'release_date.desc' => '🆕 Sorties récentes',
                    'revenue.desc' => '💰 Box-office',
                    default => 'Films'
                } }}
            @endif
        </h2>
        <div wire:loading wire:target="search,updatedSearch,updatedMediaType,updatedSortBy,loadMore">
            <svg class="animate-spin w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    {{-- Results grid --}}
    @if(empty($results))
        <div class="glass rounded-2xl p-12 text-center">
            <svg class="w-14 h-14 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p class="text-slate-400">Aucun résultat trouvé.</p>
        </div>
    @else
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 sm:gap-3">
            @foreach($results as $item)
                @php
                    $type = $item['media_type'] ?? ($mediaType !== 'all' ? $mediaType : 'movie');
                    $title = $type === 'tv' ? ($item['name'] ?? $item['title'] ?? '') : ($item['title'] ?? $item['name'] ?? '');
                    $year = !empty($item['release_date']) ? substr($item['release_date'], 0, 4) : (!empty($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : null);
                @endphp
                <a href="{{ route('movie.detail', ['type' => $type, 'tmdbId' => $item['id']]) }}"
                   wire:navigate
                   wire:key="result-{{ $item['id'] }}-{{ $type }}"
                   class="group relative card-hover rounded-xl overflow-hidden bg-[#1a1a27]">
                    @if(!empty($item['poster_path']))
                        <img src="https://image.tmdb.org/t/p/w342{{ $item['poster_path'] }}"
                             alt="{{ $title }}"
                             loading="lazy"
                             class="w-full aspect-[2/3] object-cover">
                    @else
                        <div class="w-full aspect-[2/3] bg-[#242436] flex items-center justify-center">
                            <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                            </svg>
                        </div>
                    @endif

                    @if(!empty($item['vote_average']) && $item['vote_average'] > 0)
                    <div class="absolute top-1.5 right-1.5">
                        <div class="px-1.5 py-0.5 rounded bg-black/70 backdrop-blur-sm flex items-center gap-0.5">
                            <svg class="w-2.5 h-2.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-xs text-amber-400 font-medium">{{ number_format($item['vote_average'], 1) }}</span>
                        </div>
                    </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="absolute bottom-0 p-2">
                            <p class="text-xs font-medium text-white line-clamp-2">{{ $title }}</p>
                            @if($year)
                                <p class="text-xs text-slate-400 mt-0.5">{{ $year }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Load more --}}
        @if($hasMore)
            <div class="mt-8 text-center">
                <button wire:click="loadMore"
                        wire:loading.attr="disabled"
                        wire:target="loadMore"
                        class="px-8 py-3 glass rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-white/10 transition-all border border-white/10 inline-flex items-center gap-2">
                    <svg wire:loading wire:target="loadMore" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="loadMore">Charger plus</span>
                    <span wire:loading wire:target="loadMore">Chargement...</span>
                </button>
            </div>
        @endif
    @endif
</div>
