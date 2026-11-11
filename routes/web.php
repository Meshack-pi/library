<?php

use App\Livewire\Admin;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Catalogue;
use App\Livewire\Librarian;
use App\Livewire\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn (Request $request): RedirectResponse => to_route($request->user()->homeRoute()))
        ->name('dashboard');

    Route::post('/logout', function (Request $request): RedirectResponse {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    })->name('logout');

    Route::livewire('/member', Member\Dashboard::class)
        ->middleware('role:member')
        ->name('member.dashboard');

    Route::prefix('catalogue')->name('catalogue.')->middleware('permission:catalogue.view')->group(function () {
        Route::livewire('/', Catalogue\Index::class)->name('index');
        Route::livewire('/{book}', Catalogue\Show::class)->name('show');
    });

    Route::prefix('librarian')->name('librarian.')->group(function () {
        Route::livewire('/', Librarian\Dashboard::class)->middleware('permission:loans.issue')->name('dashboard');
        Route::livewire('/circulation', Librarian\IssueReturn::class)->middleware('permission:loans.issue')->name('circulation');
        Route::livewire('/books', Librarian\Books::class)->middleware('permission:books.manage')->name('books');
        Route::livewire('/members', Librarian\Members::class)->middleware('permission:members.manage')->name('members');
        Route::livewire('/reservations', Librarian\Reservations::class)->middleware('permission:reservations.manage')->name('reservations');
        Route::livewire('/fines', Librarian\Fines::class)->middleware('permission:fines.record-payment')->name('fines');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/reports', Admin\Reports::class)->middleware('permission:reports.view')->name('reports');
        Route::livewire('/librarians', Admin\Librarians::class)->middleware('permission:librarians.manage')->name('librarians');
        Route::livewire('/settings', Admin\Settings::class)->middleware('permission:settings.manage')->name('settings');
    });
});
