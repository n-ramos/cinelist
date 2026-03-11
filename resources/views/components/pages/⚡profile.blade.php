<?php

use App\Models\UserMovie;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Mon profil')] class extends Component
{
    // Infos
    public string $name = '';
    public string $email = '';
    public bool $savedInfo = false;

    // Mot de passe
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $savedPassword = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateInfo(): void
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update(['name' => $this->name, 'email' => $this->email]);

        $this->savedInfo = true;
        $this->dispatch('info-saved');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'Mot de passe actuel incorrect.');

            return;
        }

        auth()->user()->update(['password' => Hash::make($this->password)]);

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->savedPassword = true;
    }

    public function with(RecommendationService $recommendations): array
    {
        $user = auth()->user();
        $stats = $recommendations->userStats($user);
        $affinities = $recommendations->genreAffinities($user);

        $genreNames = \App\Models\Genre::whereIn('tmdb_id', array_keys($affinities))
            ->pluck('name', 'tmdb_id')
            ->toArray();

        $topAffinities = collect($affinities)
            ->take(8)
            ->map(fn ($score, $id) => [
                'name' => $genreNames[$id] ?? "Genre #$id",
                'score' => $score,
                'percent' => round($score * 100),
            ])
            ->values();

        $recentActivity = UserMovie::forUser($user->id)
            ->with('movie')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return compact('stats', 'topAffinities', 'recentActivity');
    }
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Header profil --}}
    <div class="flex items-center gap-4 sm:gap-6 mb-8 sm:mb-10">
        <div class="w-14 h-14 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-rose-500 to-purple-600 flex items-center justify-center text-lg sm:text-2xl font-bold text-white shadow-2xl shadow-rose-500/30 shrink-0">
            {{ auth()->user()->initials() }}
        </div>
        <div class="min-w-0">
            <h1 class="text-xl sm:text-3xl font-bold text-white truncate">{{ auth()->user()->name }}</h1>
            <p class="text-slate-400 mt-0.5 text-sm truncate">{{ auth()->user()->email }}</p>
            <p class="text-xs text-slate-600 mt-1">Membre depuis {{ auth()->user()->created_at->translatedFormat('F Y') }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-2.5 sm:gap-4 mb-8 sm:mb-10">
        <div class="glass rounded-2xl p-3 sm:p-5 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-white">{{ $stats['total_watched'] }}</p>
            <p class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Vus</p>
        </div>
        <div class="glass rounded-2xl p-3 sm:p-5 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-white">{{ $stats['total_watchlist'] }}</p>
            <p class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">À voir</p>
        </div>
        <div class="glass rounded-2xl p-3 sm:p-5 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-white">
                {{ $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '—' }}
            </p>
            <p class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Note moy.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">

        {{-- Affinités genres --}}
        <div class="glass rounded-2xl p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                Affinités par genre
            </h2>

            @if($topAffinities->isEmpty())
                <div class="text-center py-6">
                    <p class="text-slate-500 text-sm">Notez des films vus pour voir vos affinités</p>
                    <a href="{{ route('watched') }}" wire:navigate
                       class="inline-flex mt-2 text-sm text-rose-400 hover:text-rose-300 transition-colors">
                        Mes films vus →
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($topAffinities as $affinity)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-slate-300 font-medium">{{ $affinity['name'] }}</span>
                                <span class="text-xs text-slate-500">{{ $affinity['percent'] }}%</span>
                            </div>
                            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-rose-600 to-rose-400 transition-all"
                                     style="width: {{ $affinity['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Activité récente --}}
        <div class="glass rounded-2xl p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Activité récente
            </h2>

            @if($recentActivity->isEmpty())
                <p class="text-slate-500 text-sm text-center py-6">Aucune activité pour l'instant</p>
            @else
                <div class="space-y-1.5">
                    @foreach($recentActivity as $item)
                        <a href="{{ route('movie.detail', ['type' => $item->movie->type->value, 'tmdbId' => $item->movie->tmdb_id]) }}"
                           wire:navigate
                           wire:key="act-{{ $item->id }}"
                           class="flex items-center gap-3 hover:bg-white/5 rounded-xl p-2 transition-all -mx-2 group">
                            @if($item->movie->posterUrl())
                                <img src="{{ $item->movie->posterUrl('w92') }}"
                                     alt="{{ $item->movie->title }}"
                                     class="w-7 h-10 rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-7 h-10 rounded-lg bg-[#242436] shrink-0"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-white truncate group-hover:text-rose-400 transition-colors">
                                    {{ $item->movie->title }}
                                </p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs px-1.5 py-0.5 rounded font-medium
                                        {{ $item->status->value === 'watched' ? 'text-emerald-400 bg-emerald-500/10' :
                                           ($item->status->value === 'watchlist' ? 'text-rose-400 bg-rose-500/10' :
                                           ($item->status->value === 'proposed' ? 'text-amber-400 bg-amber-500/10' : 'text-slate-400 bg-white/5')) }}">
                                        {{ $item->status->label() }}
                                    </span>
                                    @if($item->rating)
                                        <span class="text-xs text-amber-400">★ {{ $item->rating }}/10</span>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs text-slate-600 shrink-0 hidden sm:block">{{ $item->updated_at->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

        {{-- Modifier les infos --}}
        <div class="glass rounded-2xl p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-white mb-4">Informations du compte</h2>

            <form wire:submit="updateInfo" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Nom</label>
                    <input type="text"
                           wire:model="name"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                    @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Email</label>
                    <input type="email"
                           wire:model="email"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                    @error('email') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition-all disabled:opacity-50">
                        Enregistrer
                    </button>
                    @if($savedInfo)
                        <span wire:poll.2s="$set('savedInfo', false)"
                              class="text-xs text-emerald-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Sauvegardé
                        </span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Changer le mot de passe --}}
        <div class="glass rounded-2xl p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-white mb-4">Changer le mot de passe</h2>

            <form wire:submit="updatePassword" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Mot de passe actuel</label>
                    <input type="password"
                           wire:model="current_password"
                           autocomplete="current-password"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                    @error('current_password') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Nouveau mot de passe</label>
                    <input type="password"
                           wire:model="password"
                           autocomplete="new-password"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                    @error('password') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Confirmer</label>
                    <input type="password"
                           wire:model="password_confirmation"
                           autocomplete="new-password"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 transition-all">
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition-all disabled:opacity-50">
                        Mettre à jour
                    </button>
                    @if($savedPassword)
                        <span wire:poll.2s="$set('savedPassword', false)"
                              class="text-xs text-emerald-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Mis à jour
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
