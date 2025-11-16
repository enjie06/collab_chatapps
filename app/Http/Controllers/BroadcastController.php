<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    // Buat channel
    public function create()
    {
        $friends = auth()->user()->friends(); 
        return view('chat.create-broadcast', compact('friends'));
    }

    // Lihat channel
    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'member_ids' => 'required|array',
        ]);

        // Buat channel broadcast
        $broadcast = Conversation::create([
            'name'   => $request->title,
            'type'   => 'broadcast',
            'avatar' => null,
        ]);

        // Pembuat = admin
        $broadcast->users()->attach(Auth::id(), ['role' => 'admin']);

        // Subscriber lain
        foreach ($request->member_ids as $memberId) {
            $broadcast->users()->attach($memberId, ['role' => 'subscriber']);
        }

        return redirect()->route('chat.index')
            ->with('success', 'Channel broadcast berhasil dibuat!');
    }

    // Informasi channel
    public function info($id)
    {
        $broadcast = Conversation::with('users')
            ->where('type', 'broadcast')
            ->findOrFail($id);

        $me = Auth::id();

        $pivot = $broadcast->users()->where('user_id', $me)->first()->pivot;
        $isAdmin = $pivot->role === 'admin';

        return view('chat.broadcast-info', [
            'broadcast' => $broadcast,
            'members'   => $broadcast->users,
            'isAdmin'   => $isAdmin,
        ]);
    }

    // Nama grup
    public function updateName(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $bc = Conversation::findOrFail($id);
        $this->authorizeAdmin($bc);

        $bc->update(['name' => $request->name]);

        return back()->with('success', 'Nama channel berhasil diubah.');
    }

    // Foto grup
    public function updatePhoto(Request $request, $id)
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        $bc = Conversation::findOrFail($id);
        $this->authorizeAdmin($bc);

        $path = $request->file('avatar')->store('broadcast_avatars', 'public');
        $bc->update(['avatar' => $path]);

        return back()->with('success', 'Foto channel berhasil diperbarui.');
    }

    // Tambah anggota
    public function addMember(Request $request, $id)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $bc = Conversation::findOrFail($id);
        $this->authorizeAdmin($bc);

        if (!$bc->users->contains($request->user_id)) {
            $bc->users()->attach($request->user_id, ['role' => 'subscriber']);
        }

        return back()->with('success', 'Subscriber baru berhasil ditambahkan.');
    }

    // Hapus anggota
    public function removeMember($id, $memberId)
    {
        $bc = Conversation::findOrFail($id);
        $this->authorizeAdmin($bc);

        $bc->users()->detach($memberId);

        return back()->with('success', 'Subscriber berhasil dikeluarkan.');
    }

    // Delete channel
    public function delete($id)
    {
        $bc = Conversation::findOrFail($id);
        $this->authorizeAdmin($bc);

        $bc->delete();

        return redirect()->route('chat.index')->with('success', 'Channel broadcast dihapus.');
    }

    // Admin
    private function authorizeAdmin($broadcast)
    {
        $role = $broadcast->users()
            ->where('user_id', Auth::id())
            ->first()
            ->pivot
            ->role;

        abort_if($role !== 'admin', 403, "Hanya admin yang boleh melakukan aksi ini.");
    }
}
