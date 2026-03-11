<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

new #[Layout('layouts.app')] #[Title('Inscription')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);

        $this->redirectRoute('dashboard', navigate: true);
    }
};
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-700 shadow-2xl shadow-rose-500/40 mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">CineList</h1>
            <p class="text-slate-400 mt-1 text-sm">Votre catalogue de films & séries</p>
        </div>

        <div class="glass rounded-2xl p-8 shadow-2xl">
            <h2 class="text-xl font-semibold text-white mb-6">Créer un compte</h2>

            <form wire:submit="register" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Nom</label>
                    <input type="text"
                           wire:model="name"
                           autocomplete="name"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500/50 transition-all"
                           placeholder="Jean Dupont">
                    @error('name') <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                    <input type="email"
                           wire:model="email"
                           autocomplete="email"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500/50 transition-all"
                           placeholder="vous@exemple.com">
                    @error('email') <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Mot de passe</label>
                    <input type="password"
                           wire:model="password"
                           autocomplete="new-password"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500/50 transition-all"
                           placeholder="••••••••">
                    @error('password') <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Confirmer le mot de passe</label>
                    <input type="password"
                           wire:model="password_confirmation"
                           autocomplete="new-password"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500/50 transition-all"
                           placeholder="••••••••">
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-full bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-semibold py-2.5 px-4 rounded-xl transition-all shadow-lg shadow-rose-500/25 disabled:opacity-50 flex items-center justify-center gap-2">
                    <svg wire:loading class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Créer mon compte
                </button>
            </form>

            <p class="text-center text-sm text-slate-400 mt-6">
                Déjà un compte ?
                <a href="{{ route('login') }}" wire:navigate class="text-rose-400 hover:text-rose-300 font-medium transition-colors">
                    Se connecter
                </a>
            </p>
        </div>
    </div>
</div>
