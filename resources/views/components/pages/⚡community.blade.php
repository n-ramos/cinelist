<?php

use App\Enums\MovieStatus;
use App\Models\User;
use App\Models\UserMovie;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Communauté')] class extends Component
{
    #[Url]
    public ?int $userId = null;

    public string $tab = 'watchlist';

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function selectUser(int $userId): void
    {
        $this->userId = $userId;
        $this->tab = 'watchlist';
    }

    public function with(): array
    {
        $users = User::where('id', '!=', auth()->id())
            ->withCount([
                'userMovies as watchlist_count' => fn ($q) => $q->where('status', MovieStatus::Watchlist->value),
                'userMovies as watched_count' => fn ($q) => $q->where('status', MovieStatus::Watched->value),
            ])
            ->orderBy('name')
            ->get();

        $selectedUser = $this->userId ? User::find($this->userId) : null;

        $items = collect();

        if ($selectedUser && $selectedUser->id !== auth()->id()) {
            $status = $this->tab === 'watched' ? MovieStatus::Watched : MovieStatus::Watchlist;
            $items = UserMovie::forUser($selectedUser->id)
                ->withStatus($status)
                ->with('movie')
                ->orderByDesc('created_at')
                ->get();
        }

        return compact('users', 'selectedUser', 'items');
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Header --}}
    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-white">Communauté</h1>
        <p class="text-slate-400 mt-0.5 text-sm">Explorez les listes de vos amis</p>
    </div>

    @if($users->isEmpty())
        <div class="glass rounded-2xl p-12 text-center">
            <svg class="w-14 h-14 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-slate-400 text-base font-medium">Aucun autre membre pour l'instant</p>
        </div>
    @else

        {{-- User list --}}
        <div class="flex gap-3 overflow-x-auto pb-3 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible mb-6 sm:mb-8 scrollbar-hide">
            @foreach($users as $user)
                <button wire:click="selectUser({{ $user->id }})"
                        wire:key="user-{{ $user->id }}"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl transition-all shrink-0 border {{ $userId === $user->id ? 'glass border-rose-500/40 bg-rose-500/5' : 'glass border-white/5 hover:border-white/15 hover:bg-white/5' }}">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-rose-500 to-purple-600 flex items-center justify-center text-sm font-bold text-white shrink-0">
                        {{ $user->initials() }}
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-semibold {{ $userId === $user->id ? 'text-rose-400' : 'text-white' }} whitespace-nowrap">{{ $user->name }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5 whitespace-nowrap">
                            {{ $user->watchlist_count }} à voir · {{ $user->watched_count }} vus
                        </p>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Selected user list --}}
        @if($selectedUser)
            <div>
                {{-- User header --}}
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-rose-500 to-purple-600 flex items-center justify-center text-sm font-bold text-white shrink-0">
                        {{ $selectedUser->initials() }}
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">{{ $selectedUser->name }}</h2>
                        <p class="text-xs text-slate-500">{{ $selectedUser->watchlist_count }} à voir · {{ $selectedUser->watched_count }} vus</p>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="flex gap-1 glass rounded-xl p-1 w-fit mb-5">
                    <button wire:click="$set('tab', 'watchlist')"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium transition-all {{ $tab === 'watchlist' ? 'bg-white/15 text-white' : 'text-slate-400 hover:text-white' }}">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="{{ $tab === 'watchlist' ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            À voir
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'watchlist' ? 'bg-rose-500/30 text-rose-300' : 'bg-white/10 text-slate-500' }}">{{ $selectedUser->watchlist_count }}</span>
                        </span>
                    </button>
                    <button wire:click="$set('tab', 'watched')"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium transition-all {{ $tab === 'watched' ? 'bg-white/15 text-white' : 'text-slate-400 hover:text-white' }}">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="{{ $tab === 'watched' ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Vus
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'watched' ? 'bg-rose-500/30 text-rose-300' : 'bg-white/10 text-slate-500' }}">{{ $selectedUser->watched_count }}</span>
                        </span>
                    </button>
                </div>

                {{-- Movies --}}
                @if($items->isEmpty())
                    <div class="glass rounded-2xl p-10 text-center">
                        <p class="text-slate-400 text-sm">{{ $tab === 'watchlist' ? 'Aucun film dans la liste.' : 'Aucun film vu.' }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        @foreach($items as $userMovie)
                            <a href="{{ route('movie.detail', ['type' => $userMovie->movie->type->value, 'tmdbId' => $userMovie->movie->tmdb_id]) }}"
                               wire:navigate
                               wire:key="cm-{{ $userMovie->id }}"
                               class="glass rounded-2xl overflow-hidden hover:bg-white/5 transition-all group flex gap-3 p-3">
                                {{-- Poster --}}
                                <div class="shrink-0 w-14 rounded-xl overflow-hidden">
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
                                    <p class="font-medium text-white text-sm line-clamp-2 leading-snug group-hover:text-rose-400 transition-colors">{{ $userMovie->movie->title }}</p>
                                    <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                        <span class="text-xs text-slate-500">{{ $userMovie->movie->year() }}</span>
                                        <span class="text-xs px-1.5 py-0.5 rounded {{ $userMovie->movie->type === \App\Enums\MediaType::TV ? 'bg-blue-500/20 text-blue-400' : 'bg-rose-500/20 text-rose-400' }}">
                                            {{ $userMovie->movie->type->label() }}
                                        </span>
                                        @if($tab === 'watched' && $userMovie->rating)
                                            <div class="flex items-center gap-0.5">
                                                <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                <span class="text-xs text-amber-400 font-medium">{{ $userMovie->rating }}/10</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="glass rounded-2xl p-10 text-center">
                <svg class="w-12 h-12 text-slate-700 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                </svg>
                <p class="text-slate-500 text-sm">Sélectionnez un membre pour voir sa liste</p>
            </div>
        @endif
    @endif
</div>
