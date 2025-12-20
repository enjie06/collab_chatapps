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
            ->with([
                'messages',
                'users' => function($q){
                    $q->withPivot('deleted_at','last_read_message_id');
                }
            ])
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
            $visibleMessages = $c->messages->filter(function($m) use ($pivot, $c) {

                // hide pesan sebelum clear
                if ($pivot->last_cleared_at && $m->created_at <= $pivot->last_cleared_at) {
                    return false;
                }

                // kalau grup & user sudah keluar → batasi sampai deleted_at
                if ($c->type === 'group' && $pivot->deleted_at && $m->created_at > $pivot->deleted_at) {
                    return false;
                }

                return true;
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

        $conversation = Conversation::with(['users'])
            ->whereHas('users', fn($q) => $q->where('user_id', $me))
            ->findOrFail($id);

        $type        = $conversation->type;
        $isPrivate   = $type === 'private';
        $isGroup     = $type === 'group';
        $isBroadcast = $type === 'broadcast';

        $pivot = $conversation->users
            ->firstWhere('id', $me)
            ->pivot;

        $lastRead = $pivot->last_read_message_id ?? 0;

        $messages = $conversation->messages()
            ->when($pivot->last_cleared_at, fn($q) =>
                $q->where('created_at', '>', $pivot->last_cleared_at)
            )
            ->when($pivot->deleted_at, fn($q) =>
                $q->where('created_at', '<=', $pivot->deleted_at)
            )
            ->with(['user', 'replyTo.user', 'attachment'])
            ->orderBy('id')
            ->get();

        $lastVisible = $messages->last();

        $conversation->users()->updateExistingPivot($me, [
            'last_read_message_id' => $lastVisible?->id ?? 0,
        ]);

        $canSend   = true;
        $otherUser = null;
        $members   = collect();

        if ($isPrivate) {
            $otherUser = $conversation->users->firstWhere('id', '!=', $me);
            $friendship = Friendship::between($me, $otherUser->id)->first();

            $canSend = $friendship
                && $friendship->status === 'accepted'
                && !$friendship->is_blocked;
        }

        if ($isGroup) {
            $members = $conversation->users;
        }

        if ($isBroadcast) {
            $role = $pivot->role;
            $canSend = ($role === 'admin');
            $members = $conversation->users;
        }

        return view('chat.show', compact(
            'conversation',
            'messages',
            'lastRead',
            'isGroup',
            'isPrivate',
            'isBroadcast',
            'otherUser',
            'members',
            'canSend'
        ));
    }

    // Kirim pesan baru (untuk form biasa)
    public function sendMessage(Request $request, $id)
    {
        $me = Auth::id();

        $request->validate([
            'content'     => 'nullable|string|max:1000',
            'attachment'  => 'nullable|file|max:20480',
            'voice_note'  => 'nullable|file|max:20480',
        ]);

        $conversation = Conversation::with('users')->findOrFail($id);

        // Cek: kalau ini grup dan user sudah keluar (deleted_at != null) → tolak
        if ($conversation->type === 'group') {
            $pivot = $conversation->users->firstWhere('id', $me)?->pivot;

            if ($pivot && $pivot->deleted_at !== null) {
                return back()->with('error', 'Kamu sudah keluar dari grup ini. Tidak bisa mengirim pesan lagi.');
            }
        }

        // === BROADCAST GUARD ===
        if ($conversation->type === 'broadcast') {
            $pivot = $conversation->users->firstWhere('id', $me)?->pivot;

            if (
                !$pivot ||
                $pivot->role !== 'admin' ||
                $pivot->deleted_at !== null
            ) {
                abort(403, 'Broadcast hanya bisa dikirim oleh admin.');
            }
        }

        // Ambil lawan bicara khusus private
        $otherUser = $conversation->type === 'private'
            ? $conversation->users->firstWhere('id', '!=', $me)
            : null;

        // Cek friendship hanya untuk private
        if ($otherUser) {
            $friendship = Friendship::between($me, $otherUser->id)->first();

            if (
                !$friendship ||
                $friendship->status !== 'accepted' ||
                $friendship->is_blocked // blokir dua arah
            ) {
                return back()->with('error', 'Pesan tidak dapat dikirim.');
            }
        }

        $content = trim((string) $request->input('content'));

        if ($content === '' && !$request->hasFile('attachment')) {
            return back()->with('error', 'Pesan tidak boleh kosong.');
        }

        // JIKA ADA FILE TAPI TEXT KOSONG → ISI DUMMY
        if ($content === '' && $request->hasFile('attachment')) {
            $content = '[FILE]';
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id'         => $me,
            'content'         => $content, // TIDAK PERNAH NULL
            'reply_to_id'     => $request->reply_to_id,
        ]);

        // Simpan attachment
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mime  = $file->getClientMimeType();
            $type  = explode('/', $mime)[0];
            $path  = $file->store('attachments', 'public');

            \App\Models\Attachment::create([
                'message_id'    => $message->id,
                'file_path'     => $path,
                'file_type'     => $type,
                'original_name' => $file->getClientOriginalName(),
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

        if ($conversation->type === 'private') {
            $otherUserId = $otherUser?->id;

            // Pengirim: selalu munculkan lagi chat di sidebar
            $conversation->users()->updateExistingPivot($me, [
                'deleted_at' => null,
            ]);

            // Penerima: kalau dia pernah hapus chat, munculkan lagi saat ada pesan baru
            if ($otherUserId) {
                $conversation->users()->updateExistingPivot($otherUserId, [
                    'deleted_at' => null,
                ]);
            }
        }

        return back();
    }

    public function createConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $me    = Auth::id();
        $other = $request->user_id;

        // Cek apakah percakapan sudah ada
        $conversation = Conversation::where('type', 'private')
            ->whereHas('users', fn($q) => $q->where('user_id', $me))
            ->whereHas('users', fn($q) => $q->where('user_id', $other))
            ->first();

        // Jika belum ada, buat baru
        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
            ]);

            $conversation->users()->attach([$me, $other]);
        } else {
            // Kalau sudah ada, minimal pastikan chat ini MUNCUL lagi di sidebar
            $conversation->users()->updateExistingPivot($me, [
                'deleted_at' => null,
            ]);
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
        $userId = Auth::id();

        // SEMUA TIPE: hanya hapus history user ini
        $conversation->users()->updateExistingPivot($userId, [
            'last_cleared_at' => now(),
        ]);

        return redirect()->route('chat.index')
            ->with('success', 'Chat disembunyikan.');
    }

    public function downloadAttachment($attachmentId)
    {
        $attachment = \App\Models\Attachment::findOrFail($attachmentId);
        
        // Cek apakah user berhak mengakses file ini
        $message = $attachment->message;
        $conversation = $message->conversation;
        
        if (!$conversation->users->contains(auth()->id())) {
            abort(403, 'Unauthorized');
        }
        
        $filePath = storage_path('app/public/' . $attachment->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        
        // Buat nama file yang lebih friendly
        $originalName = basename($attachment->file_path);
        $userName = $message->user->name;
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $userName);
        $fileName = $safeName . '_' . $originalName;
        
        return response()->download($filePath, $attachment->original_name);
    }

    // AJAX: Get messages untuk dynamic chat
    public function getMessages(Conversation $conversation)
    {
        // Cek authorization
        if (!$conversation->users->contains(auth()->id())) {
            abort(403);
        }

        $pivot = $conversation->users->firstWhere('id', auth()->id())->pivot;

        // Filter messages berdasarkan cleared_at dan deleted_at
        $messages = $conversation->messages()
            ->with('user')
            ->when($pivot->last_cleared_at, fn($q) =>
                $q->where('created_at', '>', $pivot->last_cleared_at)
            )
            ->when($pivot->deleted_at && $conversation->type === 'group', fn($q) =>
                $q->where('created_at', '<=', $pivot->deleted_at)
            )
            ->orderBy('created_at', 'asc')
            ->get();

        // Tentukan nama dan avatar conversation
        $name = $conversation->name;
        $avatar = $conversation->avatar ? asset('storage/' . $conversation->avatar) : asset('images/default-group.png');

        if ($conversation->type === 'private') {
            $partner = $conversation->users->where('id', '!=', auth()->id())->first();
            $name = $partner->name ?? 'User';
            $avatar = $partner->avatar ? asset('storage/' . $partner->avatar) : asset('images/default-avatar.png');
        }

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'name' => $name,
                'avatar' => $avatar,
                'type' => $conversation->type
            ],
            'messages' => $messages
        ]);
    }

    // AJAX: Send message untuk dynamic chat
    public function sendMessageAjax(Request $request, Conversation $conversation)
    {
        $request->validate([
            'content' => 'required|string|max:5000'
        ]);

        // Cek authorization
        if (!$conversation->users->contains(auth()->id())) {
            abort(403);
        }

        // Cek apakah user sudah keluar dari grup
        if ($conversation->type === 'group') {
            $pivot = $conversation->users->firstWhere('id', auth()->id())?->pivot;

            if ($pivot && $pivot->deleted_at !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu sudah keluar dari grup ini.'
                ], 403);
            }
        }

        // === BROADCAST GUARD ===
        if ($conversation->type === 'broadcast') {
            $pivot = $conversation->users->firstWhere('id', auth()->id())?->pivot;

            if (
                !$pivot ||
                $pivot->role !== 'admin' ||
                $pivot->deleted_at !== null
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Broadcast hanya bisa dikirim oleh admin.'
                ], 403);
            }
        }

        // Untuk private chat, cek friendship
        if ($conversation->type === 'private') {
            $otherUser = $conversation->users->where('id', '!=', auth()->id())->first();
            $friendship = Friendship::between(auth()->id(), $otherUser->id)->first();

            if (!$friendship || $friendship->status !== 'accepted' || $friendship->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan tidak dapat dikirim.'
                ], 403);
            }
        }

        $message = $conversation->messages()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        // Broadcast event
        broadcast(new MessageSent($message))->toOthers();

        // Untuk private chat, munculkan kembali di sidebar jika pernah dihapus
        if ($conversation->type === 'private') {
            $otherUser = $conversation->users->where('id', '!=', auth()->id())->first();
            
            $conversation->users()->updateExistingPivot(auth()->id(), [
                'deleted_at' => null,
            ]);

            if ($otherUser) {
                $conversation->users()->updateExistingPivot($otherUser->id, [
                    'deleted_at' => null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message->load('user')
        ]);
    }
}