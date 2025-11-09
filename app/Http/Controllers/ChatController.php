<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Daftar percakapan
    public function index()
    {
        $user = Auth::user();

        $conversations = $user->conversations()
            ->with(['users', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->withPivot('last_read_message_id')

            ->get();

        // Teman = Collection<User>
        $friends  = $user->friends();

        // Pending masuk & keluar
        $incoming = $user->receivedRequestsPending()->get();
        $outgoing = Friendship::where('requester_id', $user->id)
                    ->whereIn('status', ['pending','rejected'])
                    ->with('receiver')
                    ->get();

        return view('chat.index', compact('conversations','friends','incoming','outgoing'));
    }

    // Detail percakapan (chatroom)
    public function show($id)
    {
        $conversation = Conversation::with(['messages.user', 'users'])
            ->whereHas('users', fn($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        $user = Auth::user();

        // Ambil batas baca sebelumnya dari pivot
        $lastRead = $conversation->users()
            ->where('user_id', $user->id)
            ->first()
            ->pivot
            ->last_read_message_id ?? 0;

        // Perbarui batas baca ke ID pesan terakhir yang ada
        $latestMessage = $conversation->messages->last();
        if ($latestMessage) {
            $conversation->users()->updateExistingPivot($user->id, [
                'last_read_message_id' => $latestMessage->id
            ]);
        }

        return view('chat.show', compact('conversation','lastRead'));
    }

    // Kirim pesan baru
    public function sendMessage(Request $request, $id)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $conversation = Conversation::with('users')->findOrFail($id);

        // Siapa lawan bicara (untuk 1-1)
        $otherUser = $conversation->users()->where('user_id', '!=', Auth::id())->first();

        // Kalau bukan teman (accepted), blokir kirim
        if ($otherUser && !Friendship::between(Auth::id(), $otherUser->id)
            ->where('status', 'accepted')
            ->exists()) {
            return back()->with('error', 'Kalian bukan teman lagi. Chat hanya bisa dibaca.');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id'         => Auth::id(),
            'content'         => $request->content,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return back();
    }

    public function createConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $me = Auth::id();
        $other = $request->user_id;

        // Cek apakah percakapan sudah ada
        $conversation = Conversation::whereHas('users', function ($q) use ($me) {
                $q->where('user_id', $me);
            })
            ->whereHas('users', function ($q) use ($other) {
                $q->where('user_id', $other);
            })
            ->first();

        // Jika belum ada, buat
        if (!$conversation) {
            $conversation = Conversation::create([
                'title' => null // chat 1-1 biasanya tanpa title
            ]);

            $conversation->users()->attach([$me, $other]);
        }

        return redirect()->route('chat.show', $conversation->id);
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
