<?php

use App\Models\Attachment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard.index')->name('dashboard');
    Route::livewire('/tickets', 'pages::tickets.index')->name('tickets.index');
    Route::livewire('/tickets/{ticket}', 'pages::tickets.show')->name('tickets.show');

    Route::get('/attachments/{attachment}', function (Attachment $attachment) {
        abort_unless(auth()->user()->can('view', $attachment->ticket()), 403);

        $disk = Storage::disk($attachment->disk);

        return $attachment->isImage()
            ? $disk->response($attachment->path, $attachment->original_name)
            : $disk->download($attachment->path, $attachment->original_name);
    })->name('attachments.show');
    Route::livewire('/how-to-use', 'pages::help.index')->name('help');

    Route::middleware('role:admin')->group(function () {
        Route::livewire('/users', 'pages::users.index')->name('users.index');
        Route::livewire('/categories', 'pages::categories.index')->name('categories.index');
    });
});
