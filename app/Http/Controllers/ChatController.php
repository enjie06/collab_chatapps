<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Daftar percakapan user login
    public function index()
    {
        $user = Auth::user();
        $conversations = $user->conversations()->with('users')->get();

        return view('chat.index', compact('conversations'));
    }

    // Detail percakapan (chatroom)
    public function show($id)
    {
        $conversation = Conversation::with(['messages.user', 'users'])
            ->whereHas('users', fn($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        return view('chat.show', compact('conversation'));
    }


    // Kirim pesan baru
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $conversation = Conversation::findOrFail($id);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        return redirect()->route('chat.show', $conversation->id);
    }
    public function createConversation(Request $request)
    {
        $conversation = Conversation::create(['title' => $request->title]);

        $conversation->users()->attach(Auth::id(), ['role' => 'admin']);
        $conversation->users()->attach($request->member_ids);

        return redirect()->route('chat.index');
    }
    public function createBroadcast(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'member_ids' => 'required|array',
        ]);

        // 1️⃣ Buat percakapan grup
        $conversation = Conversation::create([
            'title' => $request->title,
        ]);

        // 2️⃣ Tambahkan pembuat grup sebagai admin
        $conversation->users()->attach(Auth::id(), ['role' => 'admin']);

        // 3️⃣ Tambahkan anggota lain ke grup
        $conversation->users()->attach($request->member_ids, ['role' => 'member']);

        return redirect()->route('chat.index')->with('success', 'Grup berhasil dibuat!');
    }

}
