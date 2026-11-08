<?php

use App\Livewire\ApiKeys\ApiKeyIndex;
use App\Livewire\Channels\ChannelIndex;
use App\Livewire\Dashboard\LiveMonitor;
use App\Livewire\Products\ProductIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', LiveMonitor::class)->name('dashboard');
Route::get('/channels', ChannelIndex::class)->name('channels.index');
Route::get('/api-keys', ApiKeyIndex::class)->name('api-keys.index');
Route::get('/products', ProductIndex::class)->name('products.index');
