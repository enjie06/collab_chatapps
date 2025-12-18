<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    // CREATE
    public function create()
    {
        $friends = auth()->user()->friends();
        return view('chat.create-broadcast', compact('friends'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'member_ids'   => 'required|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $broadcast = Conversation::create([
            'name' => $request->title,
            'type' => 'broadcast',
        ]);

        // creator = admin
        $broadcast->users()->attach(Auth::id(), ['role' => 'admin']);

        // members
        foreach ($request->member_ids as $userId) {
            if ($userId != Auth::id()) {
                $broadcast->users()->attach($userId, [
                    'role' => 'member',
                ]);
            }
        }

        return redirect()
            ->route('chat.index')
            ->with('success', 'Broadcast berhasil dibuat.');
    }

    // INFO
    public function info($id)
    {
        $broadcast = Conversation::where('type', 'broadcast')->findOrFail($id);
        $me = Auth::id();

        $pivot = $broadcast->users()
            ->where('user_id', $me)
            ->first()
            ->pivot;

        $isAdmin = $pivot->role === 'admin';

        $members = $broadcast->users()
            ->whereNull('conversation_user.deleted_at')
            ->get();

        $friends = auth()->user()->friends();

        return view('chat.broadcast-info', [
            'broadcast' => $broadcast,
            'members'   => $members,
            'isAdmin'   => $isAdmin,
            'friends'   => $friends,
        ]);
    }

    // ADD MEMBER
    public function addMember(Request $request, $id)
    {
        $request->validate([
            'user_id'   => 'required|array',
            'user_id.*' => 'exists:users,id',
        ]);

        $broadcast = Conversation::findOrFail($id);
        $this->authorizeAdmin($broadcast);

        foreach ($request->user_id as $userId) {
            $broadcast->users()->syncWithoutDetaching([
                $userId => [
                    'role'       => 'member',
                    'deleted_at' => null,
                ],
            ]);
        }

        return back()->with('success', 'Member ditambahkan.');
    }

    // REMOVE MEMBER
    public function removeMember($id, $memberId)
    {
        $broadcast = Conversation::findOrFail($id);
        $this->authorizeAdmin($broadcast);

        $target = $broadcast->users()
            ->where('user_id', $memberId)
            ->whereNull('conversation_user.deleted_at')
            ->first();

        if (!$target) {
            return back()->with('error', 'Member tidak ditemukan.');
        }

        if ($target->pivot->role === 'admin') {
            return back()->with('error', 'Admin tidak bisa dikeluarkan.');
        }

        $broadcast->users()->updateExistingPivot($memberId, [
            'deleted_at' => now(),
        ]);

        return back()->with('success', 'Member dikeluarkan.');
    }

    // DELETE
    public function delete($id)
    {
        $broadcast = Conversation::findOrFail($id);
        $this->authorizeAdmin($broadcast);

        $broadcast->delete();

        return redirect()
            ->route('chat.index')
            ->with('success', 'Broadcast dihapus.');
    }

    // AUTH
    private function authorizeAdmin(Conversation $broadcast)
    {
        $role = $broadcast->users()
            ->where('user_id', Auth::id())
            ->first()
            ->pivot
            ->role;

        abort_if($role !== 'admin', 403);
    }
}
