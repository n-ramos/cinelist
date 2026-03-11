<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'auth.login')->name('login');
    Route::livewire('/register', 'auth.register')->name('register');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Public (accessible sans compte)
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('discover');
})->name('home');

Route::livewire('/discover', 'pages.discover')->name('discover');
Route::livewire('/movie/{type}/{tmdbId}', 'pages.movie-detail')->name('movie.detail');

// App (auth required)
Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages.dashboard')->name('dashboard');
    Route::livewire('/watchlist', 'pages.watchlist')->name('watchlist');
    Route::livewire('/watched', 'pages.watched')->name('watched');
    Route::livewire('/proposals', 'pages.proposals')->name('proposals');
    Route::livewire('/profile', 'pages.profile')->name('profile');
});
