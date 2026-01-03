<?php

use App\Livewire\Homepage;
use Illuminate\Support\Facades\Route;


Route::get('/', Homepage::class)->name('homepage');
Route::get('/catalog', \App\Livewire\Catalog::class)->name('catalog');
Route::get('/module/{feature}', \App\Livewire\Module::class)->name('module.show');

Route::get('/space/auth/register', \App\Livewire\Space\Auth\Register::class)->name('space.auth.register');
