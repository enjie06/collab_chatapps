<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\FriendController;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard & Profile (default Laravel Breeze)
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', fn () => redirect()->route('chat.index'))
    ->middleware(['auth'])
    ->name('dashboard');

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

    // Info mengetik
    Route::post('/chat/{id}/typing', [ChatController::class, 'typing'])->name('chat.typing');

    // Menu one-to-one
    Route::get('/user/{id}/profile', [ProfileController::class, 'show'])->name('user.profile');
    Route::delete('/chat/{id}/delete', [ChatController::class, 'deleteChat'])->name('chat.delete');
    Route::post('/friends/block/{userId}', [FriendController::class, 'block'])->name('friends.block');
    Route::post('/friends/unblock/{userId}', [FriendController::class, 'unblock'])->name('friends.unblock');

    // Grup
    Route::get('/groups/create', [GroupController::class, 'createGroup'])->name('group.create');
    Route::post('/groups/store', [GroupController::class, 'store'])->name('group.store');

    // Menu grip
    Route::get('/group/{id}/info', [GroupController::class, 'info'])->name('group.info');
    Route::post('/group/{id}/name', [GroupController::class, 'updateName'])->name('group.name');
    Route::post('/group/{id}/photo', [GroupController::class, 'updatePhoto'])->name('group.photo');
    Route::post('/group/{id}/add', [GroupController::class, 'addMember'])->name('group.add');
    Route::delete('/group/{id}/remove/{memberId}', [GroupController::class, 'removeMember'])->name('group.remove');
    Route::post('/group/{id}/promote/{memberId}', [GroupController::class, 'promote'])->name('group.promote');
    Route::post('/group/{id}/demote/{memberId}', [GroupController::class, 'demote'])->name('group.demote');
    Route::delete('/group/{id}/leave', [GroupController::class, 'leave'])->name('group.leave');
    Route::delete('/group/{id}/delete', [GroupController::class, 'delete'])->name('group.delete');
});

Route::get('/chat/download/{attachment}', [ChatController::class, 'downloadAttachment'])
    ->name('chat.download')
    ->middleware('auth');

require __DIR__.'/auth.php';
