<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CineList' }} — CineList</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#0a0a0f] text-slate-100 antialiased">

    {{-- Top nav --}}
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 sm:h-16">

                {{-- Logo --}}
                <a href="{{ auth()->check() ? route('dashboard') : route('discover') }}" wire:navigate
                   class="flex items-center gap-2 group shrink-0">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center shadow-lg shadow-rose-500/30">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/>
                        </svg>
                    </div>
                    <span class="font-bold text-base sm:text-lg tracking-tight text-white group-hover:text-rose-400 transition-colors">CineList</span>
                </a>

                @auth
                {{-- Desktop nav — connecté --}}
                <div class="hidden md:flex items-center gap-0.5">
                    <a href="{{ route('dashboard') }}" wire:navigate
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'text-white bg-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        Accueil
                    </a>
                    <a href="{{ route('discover') }}" wire:navigate
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('discover') ? 'text-white bg-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        Découvrir
                    </a>
                    <a href="{{ route('watchlist') }}" wire:navigate
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('watchlist') ? 'text-white bg-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        Ma liste
                    </a>
                    <a href="{{ route('watched') }}" wire:navigate
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('watched') ? 'text-white bg-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        Vus
                    </a>
                    <a href="{{ route('proposals') }}" wire:navigate
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('proposals') ? 'text-white bg-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        Propositions
                    </a>
                </div>

                {{-- Droite — connecté --}}
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('discover') }}" wire:navigate
                       class="hidden sm:flex p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </a>

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-2 p-1 rounded-lg hover:bg-white/5 transition-all">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-br from-rose-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white">
                                {{ auth()->user()->initials() }}
                            </div>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.outside="open = false"
                             class="absolute right-0 top-full mt-2 w-52 glass rounded-xl shadow-2xl border border-white/10 py-1 z-50">
                            <div class="px-4 py-2.5 border-b border-white/10">
                                <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-400 truncate mt-0.5">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('profile') }}" wire:navigate @click="open = false"
                               class="w-full text-left px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Mon profil
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5 transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @else
                {{-- Desktop nav — non connecté --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('discover') }}" wire:navigate
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('discover') ? 'text-white bg-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        Découvrir
                    </a>
                </div>

                {{-- Droite — non connecté --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" wire:navigate
                       class="px-3 sm:px-4 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-all">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}" wire:navigate
                       class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold bg-rose-600 hover:bg-rose-500 text-white transition-all shadow-lg shadow-rose-500/20">
                        S'inscrire
                    </a>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Bottom nav mobile (auth seulement) --}}
    @auth
    <nav class="fixed bottom-0 left-0 right-0 z-50 md:hidden glass border-t border-white/5 pb-safe">
        <div class="flex items-center justify-around px-1 h-14">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'text-rose-400' : 'text-slate-500 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('dashboard') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[9px] font-medium">Accueil</span>
            </a>
            <a href="{{ route('discover') }}" wire:navigate
               class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl transition-all {{ request()->routeIs('discover') ? 'text-rose-400' : 'text-slate-500 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('discover') ? '2.5' : '2' }}" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="text-[9px] font-medium">Découvrir</span>
            </a>
            <a href="{{ route('watchlist') }}" wire:navigate
               class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl transition-all {{ request()->routeIs('watchlist') ? 'text-rose-400' : 'text-slate-500 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('watchlist') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                <span class="text-[9px] font-medium">Ma liste</span>
            </a>
            <a href="{{ route('watched') }}" wire:navigate
               class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl transition-all {{ request()->routeIs('watched') ? 'text-rose-400' : 'text-slate-500 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('watched') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    @if(request()->routeIs('watched'))
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    @endif
                </svg>
                <span class="text-[9px] font-medium">Vus</span>
            </a>
            <a href="{{ route('proposals') }}" wire:navigate
               class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl transition-all {{ request()->routeIs('proposals') ? 'text-rose-400' : 'text-slate-500 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('proposals') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-[9px] font-medium">Propositions</span>
            </a>
        </div>
    </nav>
    @endauth

    <main class="pt-14 sm:pt-16 @auth pb-14 md:pb-0 @endauth">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
