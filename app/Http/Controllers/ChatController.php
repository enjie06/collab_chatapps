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

        // Ambil semua percakapan yang pernah user ikuti
        $conversations = Conversation::whereHas('users', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['messages', 'users'])
            ->get();

        // Filter PRIVATE (hide bila user sudah hapus)
        $conversations = $conversations->filter(function($c) use ($user) {
            $pivot = $c->users->firstWhere('id', $user->id)->pivot;

            if ($c->type === 'private') {
                return $pivot->deleted_at === null;
            }

            return true; // Grup & broadcast tetap tampil
        });

        // Hitung pesan terakhir yang terlihat user
        $conversations = $conversations->map(function($c) use ($user) {

            $pivot = $c->users->firstWhere('id', $user->id)->pivot;
            $deletedAt = $pivot->deleted_at;

            // filter pesan yang sudah dihapus
            $visibleMessages = $c->messages->filter(function($m) use ($deletedAt) {
                if (!$deletedAt) return true;
                return $m->created_at > $deletedAt;
            });

            $c->last_visible_message = $visibleMessages->sortBy('id')->last();
            $c->last_visible_time = $c->last_visible_message?->created_at;

            return $c;
        });

        // Urutkan berdasarkan pesan terakhir
        $conversations = $conversations->sortByDesc('last_visible_time');

        // Pisahkan kategori
        $privateChats = $conversations->where('type', 'private');
        $broadcasts   = $conversations->where('type', 'broadcast');

        // GRUP LIST — grup yang belum keluar ditampilkan
        $groupChats = $conversations
            ->where('type', 'group')
            ->filter(function($c) use ($user) {
                $pivot = $c->users->firstWhere('id', $user->id)->pivot;
                return $pivot->deleted_at === null; // grup yg sudah keluar disembunyikan di sidebar kiri
            });

        // Teman & request
        $friends  = $user->friends();
        $incoming = $user->receivedRequestsPending()->get();
        $outgoing = Friendship::where('requester_id', $user->id)
                    ->whereIn('status', ['pending','rejected'])
                    ->with('receiver')
                    ->get();

        return view('chat.index', compact(
            'conversations',
            'privateChats',
            'groupChats',
            'broadcasts',
            'friends',
            'incoming',
            'outgoing'
        ));
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

        // Cek: kalau ini grup dan user sudah keluar (deleted_at != null) → tolak
        if ($conversation->type === 'group') {
            $pivot = $conversation->users->firstWhere('id', $me)?->pivot;

            if ($pivot && $pivot->deleted_at !== null) {
                return back()->with('error', 'Kamu sudah keluar dari grup ini. Tidak bisa mengirim pesan lagi.');
            }
        }

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

        if ($conversation->type === 'private') {
            // PRIVATE → hide chat dari list
            $conversation->users()->updateExistingPivot(Auth::id(), [
                'deleted_at' => now()
            ]);
        } else {
            // GROUP → hapus history chat user, tapi grup tetap muncul
            $conversation->users()->updateExistingPivot(Auth::id(), [
                'deleted_at' => now() // supaya pesan lama hilang
            ]);
        }

        return redirect()->route('chat.index')->with('success', 'Chat berhasil dihapus.');
    }
}
