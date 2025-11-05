<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

// Semua route chat dilindungi login
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{id}/send', [ChatController::class, 'sendMessage'])->name('chat.send');

    // Tambahan fitur: buat percakapan & broadcast
    Route::post('/chat/create', [ChatController::class, 'createConversation'])->name('chat.create');
    Route::post('/chat/broadcast', [ChatController::class, 'createBroadcast'])->name('chat.broadcast');
});
