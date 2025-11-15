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
            ->with(['messages' => function($q) use ($user) {
                // Ambil pesan setelah waktu user menghapus chat
                $q->when(true, function($q2) use ($user) {
                    $pivot = $q2->getQuery()->joins ?
                        null : null; // abaikan—tidak perlu pivot di sini
                });
            }, 'users'])
            ->wherePivot('deleted_at', null)
            ->get();

        // Urutkan percakapan berdasarkan pesan terbaru
        $conversations = $conversations->sortByDesc(function($c) {
            return optional($c->messages->last())->created_at;
        });

        // Teman
        $friends  = $user->friends();
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
        $me = Auth::id();
        $user = Auth::user();

        // Ambil percakapan yang user ikuti
        $conversation = Conversation::with(['messages.user', 'users'])
            ->whereHas('users', fn($q) => $q->where('user_id', $me))
            ->findOrFail($id);

        // Tipe percakapan
        $type         = $conversation->type;
        $isPrivate    = $type === 'private';
        $isGroup      = $type === 'group';
        $isBroadcast  = $type === 'broadcast';

        // Ambil pivot untuk last_read
        $pivot = $conversation->users()
            ->where('user_id', $me)
            ->first()
            ->pivot;
        $deletedAt = $pivot->deleted_at;
        $lastRead = $pivot->last_read_message_id ?? 0;

        $latestMessage = $conversation->messages()
            ->when($deletedAt, fn($q) => $q->where('created_at', '>', $deletedAt))
            ->orderBy('id', 'desc')
            ->first();

        // Update last_read_message_id
        if ($latestMessage) {
            $conversation->users()->updateExistingPivot($me, [
                'last_read_message_id' => $latestMessage->id
            ]);
        }

        // Filter pesan berdasarkan deleted_at
        $messages = $conversation->messages()
            ->when($deletedAt, function($q) use ($deletedAt) {
                $q->where('created_at', '>', $deletedAt);
            })
            ->with('user')
            ->get();

        // Default
        $canSend = true;
        $otherUser = null;
        $members = collect();

        // Private Chat
        if ($isPrivate) {

            // Ambil lawan bicara
            $otherUser = $conversation->users
                ->firstWhere('id', '!=', $me);

            // Jika tidak ketemu → percakapan rusak / tidak valid
            if (!$otherUser) {
                abort(404, "Percakapan private tidak valid.");
            }

            // Cek friendship
            $friendship = Friendship::between($me, $otherUser->id)->first();

            $isFriend  = $friendship && $friendship->status === 'accepted';
            $isBlocked = $friendship && $friendship->is_blocked;

            $canSend = $isFriend && !$isBlocked;
        }

        // Group Chat
        if ($isGroup) {
            $members = $conversation->users;
            $canSend = true; // siapa saja boleh kirim
        }

        // Broadcast Chat
        if ($isBroadcast) {

            $role = $conversation->users()
                ->where('user_id', $me)
                ->first()
                ->pivot
                ->role;

            // Hanya admin yg boleh kirim
            $canSend = ($role === 'admin');

            // Semua anggota untuk ditampilkan
            $members = $conversation->users;
        }

        return view('chat.show', [
            'conversation' => $conversation,
            'messages'     => $messages,
            'lastRead'     => $lastRead,
            'isGroup'      => $isGroup,
            'isPrivate'    => $isPrivate,
            'isBroadcast'  => $isBroadcast,
            'otherUser'    => $otherUser,
            'members'      => $members,
            'canSend'      => $canSend,
        ]);
    }

    // Kirim pesan baru
    public function sendMessage(Request $request, $id)
    {
        $me = Auth::id();

        $request->validate([
            'content'   => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|max:20480',
            'voice_note' => 'nullable|file|max:20480',
        ]);

        $conversation = Conversation::with('users')->findOrFail($id);

        // Ambil lawan bicara khusus private
        $otherUser = $conversation->type === 'private'
            ? $conversation->users->firstWhere('id', '!=', $me)
            : null;

        // Cek friendship hanya untuk private
        if ($otherUser) {
            $friendship = Friendship::between($me, $otherUser->id)->first();

            // Jika tidak berteman atau ada salah satu yg memblokir → tidak bisa kirim
            if (
                !$friendship ||
                $friendship->status !== 'accepted' ||
                $friendship->is_blocked // blokir dua arah
            ) {
                return back()->with('error', 'Pesan tidak dapat dikirim.');
            }
        }

        // Simpan pesan
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id'         => $me,
            'content'         => $request->content,
        ]);

        // Simpan attachment
        if ($request->hasFile('attachment')) {
            $file  = $request->file('attachment');
            $mime  = $file->getClientMimeType();
            $type  = explode('/', $mime)[0];
            $path  = $file->store('attachments', 'public');

            \App\Models\Attachment::create([
                'message_id' => $message->id,
                'file_path'  => $path,
                'file_type'  => $type == 'application' ? 'file' : $type,
            ]);
        }

        if ($request->hasFile('voice_note')) {
            $path = $request->file('voice_note')->store('attachments', 'public');

            \App\Models\Attachment::create([
                'message_id' => $message->id,
                'file_path'  => $path,
                'file_type'  => 'audio',
            ]);
        }

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
        $conversation = Conversation::where('type', 'private')
            ->whereHas('users', fn($q) => $q->where('user_id', $me))
            ->whereHas('users', fn($q) => $q->where('user_id', $other))
            ->first();

        // Jika belum ada, buat
        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
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
            'name' => $request->title,
            'type' => 'group',
            'avatar' => null,   // nanti bisa update foto grup
        ]);

        // 2️⃣ Tambahkan pembuat grup
        $conversation->users()->attach(Auth::id(), ['role' => 'admin']);

        // 3️⃣ Tambahkan anggota lain
        foreach ($request->member_ids as $memberId) {
            $conversation->users()->attach($memberId, ['role' => 'member']);
        }

        return redirect()->route('chat.index')->with('success', 'Grup berhasil dibuat!');
    }

    public function createGroup()
    {
        $friends = auth()->user()->friends(); // daftar teman untuk dipilih
        return view('chat.create-group', compact('friends'));
    }

    public function typing(Request $request, $id)
    {
        broadcast(new \App\Events\UserTyping(
            Auth::user(),
            $id,
            $request->typing
        ))->toOthers();

        return response()->json(['status' => 'ok']);
    }

    public function deleteChat($id)
    {
        $conversation = Conversation::findOrFail($id);

        $conversation->users()->updateExistingPivot(Auth::id(), [
            'deleted_at' => now()
        ]);

        return redirect()->route('chat.index')->with('success', 'Chat berhasil dihapus.');
    }
}
