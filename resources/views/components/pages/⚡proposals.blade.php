<?php

use App\Enums\MovieStatus;
use App\Models\UserMovie;
use App\Services\RecommendationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Propositions')] class extends Component
{
    public string $search = '';
    public string $filter = 'all';
    public bool $showAddForm = false;
    public string $newProposalSearch = '';
    public string $newProposedBy = '';

    /** @var array<int, array<string, mixed>> */
    public array $searchResults = [];

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function searchProposals(\App\Services\TmdbService $tmdb): void
    {
        if (strlen($this->newProposalSearch) < 2) {
            $this->searchResults = [];

            return;
        }

        $data = $tmdb->search($this->newProposalSearch);
        $this->searchResults = array_slice(array_values($data['results'] ?? []), 0, 8);
    }

    public function addProposal(int $tmdbId, string $mediaType, \App\Services\TmdbService $tmdb, RecommendationService $recommendations): void
    {
        $movie = $tmdb->getDetails($tmdbId, \App\Enums\MediaType::from($mediaType));

        if (! $movie) {
            return;
        }

        UserMovie::updateOrCreate(
            ['user_id' => auth()->id(), 'movie_id' => $movie->id],
            [
                'status' => MovieStatus::Proposed->value,
                'proposed_by' => $this->newProposedBy ?: null,
            ]
        );

        $this->showAddForm = false;
        $this->newProposalSearch = '';
        $this->newProposedBy = '';
        $this->searchResults = [];
        $recommendations->invalidateCache(auth()->user());
    }

    public function acceptProposal(int $userMovieId, RecommendationService $recommendations): void
    {
        UserMovie::where('id', $userMovieId)
            ->where('user_id', auth()->id())
            ->update(['status' => MovieStatus::Watchlist->value]);

        $recommendations->invalidateCache(auth()->user());
    }

    public function rejectProposal(int $userMovieId, RecommendationService $recommendations): void
    {
        UserMovie::where('id', $userMovieId)
            ->where('user_id', auth()->id())
            ->update(['status' => MovieStatus::Dismissed->value]);

        $recommendations->invalidateCache(auth()->user());
    }

    public function deleteProposal(int $userMovieId, RecommendationService $recommendations): void
    {
        UserMovie::where('id', $userMovieId)
            ->where('user_id', auth()->id())
            ->delete();

        $recommendations->invalidateCache(auth()->user());
    }

    public function with(): array
    {
        $items = UserMovie::forUser(auth()->id())
            ->withStatus(MovieStatus::Proposed)
            ->with('movie')
            ->when($this->search, fn ($q) => $q->whereHas('movie', fn ($mq) => $mq->where('title', 'ilike', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->get();

        return ['items' => $items];
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Header --}}
    <div class="flex items-start sm:items-center justify-between gap-4 mb-6 sm:mb-8 flex-wrap">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">Propositions</h1>
            <p class="text-slate-400 mt-0.5 text-sm">Films & séries proposés à regarder</p>
        </div>
        <button wire:click="$toggle('showAddForm')"
                class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition-all shadow-lg shadow-rose-500/25 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter
        </button>
    </div>

    {{-- Formulaire d'ajout --}}
    @if($showAddForm)
        <div class="glass rounded-2xl p-4 sm:p-6 mb-6 border border-white/10">
            <h3 class="text-sm font-semibold text-white mb-4">Nouvelle proposition</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Film / Série</label>
                    <input type="text"
                           wire:model.live.debounce.400ms="newProposalSearch"
                           wire:change="searchProposals"
                           placeholder="Rechercher..."
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Proposé par (optionnel)</label>
                    <input type="text"
                           wire:model="newProposedBy"
                           placeholder="Nom de la personne..."
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                </div>
            </div>

            @if(!empty($searchResults))
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                    @foreach($searchResults as $item)
                        @php
                            $type = $item['media_type'] ?? 'movie';
                            $title = $type === 'tv' ? ($item['name'] ?? '') : ($item['title'] ?? '');
                        @endphp
                        <button wire:click="addProposal({{ $item['id'] }}, '{{ $type }}')"
                                wire:key="search-{{ $item['id'] }}"
                                class="flex gap-2 p-2 glass rounded-xl hover:bg-white/10 transition-all text-left group">
                            @if(!empty($item['poster_path']))
                                <img src="https://image.tmdb.org/t/p/w92{{ $item['poster_path'] }}"
                                     alt="{{ $title }}"
                                     class="w-9 h-[52px] rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-9 h-[52px] rounded-lg bg-[#242436] shrink-0"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-white line-clamp-2 group-hover:text-rose-400 transition-colors">{{ $title }}</p>
                                <span class="text-xs {{ $type === 'tv' ? 'text-blue-400' : 'text-rose-400' }}">
                                    {{ $type === 'tv' ? 'Série' : 'Film' }}
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Recherche --}}
    <div class="glass rounded-2xl p-3 sm:p-4 mb-6">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Filtrer les propositions..."
                   class="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
        </div>
    </div>

    <p class="text-xs text-slate-500 mb-4">{{ $items->count() }} {{ $items->count() > 1 ? 'propositions' : 'proposition' }}</p>

    @if($items->isEmpty())
        <div class="glass rounded-2xl p-12 sm:p-16 text-center">
            <svg class="w-14 h-14 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-slate-400 text-base font-medium">Aucune proposition</p>
            <p class="text-slate-500 text-sm mt-1">Ajoutez des films proposés par vos amis</p>
            <button wire:click="$set('showAddForm', true)"
                    class="inline-flex mt-4 px-6 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition-all">
                Ajouter une proposition
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            @foreach($items as $userMovie)
                <div wire:key="prop-{{ $userMovie->id }}"
                     class="glass rounded-2xl overflow-hidden hover:bg-white/5 transition-all">
                    <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                       wire:navigate
                       class="flex gap-3 p-3 sm:p-4">
                        @if($userMovie->movie->posterUrl())
                            <img src="{{ $userMovie->movie->posterUrl('w185') }}"
                                 alt="{{ $userMovie->movie->title }}"
                                 loading="lazy"
                                 class="w-12 sm:w-14 rounded-xl object-cover aspect-[2/3] shrink-0">
                        @else
                            <div class="w-12 sm:w-14 aspect-[2/3] rounded-xl bg-[#242436] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0 py-0.5">
                            <p class="font-semibold text-white text-sm line-clamp-2">{{ $userMovie->movie->title }}</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-xs text-slate-500">{{ $userMovie->movie->year() }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $userMovie->movie->type === \App\Enums\MediaType::TV ? 'bg-blue-500/20 text-blue-400' : 'bg-rose-500/20 text-rose-400' }}">
                                    {{ $userMovie->movie->type->label() }}
                                </span>
                            </div>
                            @if($userMovie->proposed_by)
                                <div class="flex items-center gap-1 mt-1.5">
                                    <svg class="w-3 h-3 text-amber-400/70 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-xs text-amber-400/80 truncate">{{ $userMovie->proposed_by }}</span>
                                </div>
                            @endif
                        </div>
                    </a>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1.5 px-3 sm:px-4 pb-3 sm:pb-4">
                        <button wire:click="acceptProposal({{ $userMovie->id }})"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-medium transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            À ma liste
                        </button>
                        <button wire:click="rejectProposal({{ $userMovie->id }})"
                                class="py-2 px-3 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 text-xs font-medium transition-all">
                            Ignorer
                        </button>
                        <button wire:click="deleteProposal({{ $userMovie->id }})"
                                wire:confirm="Supprimer cette proposition ?"
                                class="p-2 rounded-xl text-slate-600 hover:text-rose-400 hover:bg-rose-500/10 transition-all">
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
