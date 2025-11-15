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
            ->where(function ($q) {
                // Tampilkan grup WALAU belum ada pesan
                $q->where('type', 'group');

                // Untuk private: tampilkan HANYA jika sudah ada pesan
                $q->orWhere(function ($q2) {
                    $q2->where('type', 'private')
                    ->whereHas('messages');
                });
            })
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

        // === LAST READ HANDLING ===
        $lastRead = $conversation->users()
            ->where('user_id', $user->id)
            ->first()
            ->pivot
            ->last_read_message_id ?? 0;

        $latestMessage = $conversation->messages->last();
        if ($latestMessage) {
            $conversation->users()->updateExistingPivot($user->id, [
                'last_read_message_id' => $latestMessage->id
            ]);
        }

        // === GROUP OR PRIVATE? ===
        $isGroup = $conversation->type === 'group';

        // Untuk group → boleh kirim pesan
        if ($isGroup) {
            $canSend = true;
            $members = $conversation->users; // untuk ditampilkan di header
            return view('chat.show', compact(
                'conversation', 'lastRead', 'isGroup', 'members', 'canSend'
            ));
        }

        // === PRIVATE CHAT LOGIC ===
        $otherUser = $conversation->users->firstWhere('id', '!=', $user->id);

        $isFriend = \App\Models\Friendship::between($user->id, $otherUser->id)
            ->where('status', 'accepted')
            ->exists();

        $canSend = $isFriend;

        return view('chat.show', compact(
            'conversation', 'lastRead', 'isGroup', 'otherUser', 'canSend'
        ));
    }

    // Kirim pesan baru
   public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'content'   => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|max:20480',
            'voice_note' => 'nullable|file|max:20480',
        ]);

        $conversation = Conversation::with('users')->findOrFail($id);

        $otherUser = $conversation->users()->where('user_id', '!=', Auth::id())->first();

        if ($otherUser && !Friendship::between(Auth::id(), $otherUser->id)
            ->where('status', 'accepted')
            ->exists()) {
            return back()->with('error', 'Kalian bukan teman lagi. Chat hanya dapat dibaca.');
        }

        // 1️⃣ SIMPAN PESAN
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id'         => Auth::id(),
            'content'         => $request->content, // boleh kosong
        ]);

        // 2️⃣ JIKA ADA FILE, SIMPAN ATTACHMENT
        if ($request->hasFile('attachment')) {

            $file      = $request->file('attachment');
            $mime      = $file->getClientMimeType();
            $type      = explode('/', $mime)[0];
            $path      = $file->store('attachments', 'public');

            // Buat attachment
            \App\Models\Attachment::create([
                'message_id' => $message->id,
                'file_path'  => $path,
                'file_type'  => $type == 'application' ? 'file' : $type,
            ]);
        }

        if ($request->hasFile('voice_note')) {

            $file = $request->file('voice_note');
            $path = $file->store('attachments', 'public');

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
}
