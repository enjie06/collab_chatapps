<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FriendController;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard & Profile (default Laravel Breeze)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
});

// Semua route chat dilindungi login
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{id}/send', [ChatController::class, 'sendMessage'])->name('chat.send');

    // Menambah teman
    Route::get('/friends', [\App\Http\Controllers\FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends/send', [\App\Http\Controllers\FriendController::class, 'sendRequest'])->name('friends.send');
    Route::post('/friends/accept/{id}', [\App\Http\Controllers\FriendController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/reject/{id}', [\App\Http\Controllers\FriendController::class, 'reject'])->name('friends.reject');
    Route::delete('/friends/remove/{userId}', [\App\Http\Controllers\FriendController::class, 'remove'])->name('friends.remove');
    Route::delete('/friends/clear/{friendshipId}', [\App\Http\Controllers\FriendController::class, 'clearRejected'])->name('friends.clear');

    // Tambahan fitur: buat percakapan & broadcast
    Route::post('/chat/create', [ChatController::class, 'createConversation'])->name('chat.create');
    Route::post('/chat/broadcast', [ChatController::class, 'createBroadcast'])->name('chat.broadcast');

    // Grup
    Route::get('/groups/create', [ChatController::class, 'createGroup'])->name('group.create');
    Route::post('/groups/store', [ChatController::class, 'createBroadcast'])->name('group.store');

    // Info mengetik
    Route::post('/chat/{id}/typing', [ChatController::class, 'typing'])->name('chat.typing');

    // Menu one-to-one
    Route::get('/user/{id}/profile', [ProfileController::class, 'show'])->name('user.profile');
    Route::delete('/chat/{id}/delete', [ChatController::class, 'deleteChat'])->name('chat.delete');
    Route::post('/friends/block/{userId}', [FriendController::class, 'block'])->name('friends.block');
    Route::post('/friends/unblock/{userId}', [FriendController::class, 'unblock'])->name('friends.unblock');
});

require __DIR__.'/auth.php';
